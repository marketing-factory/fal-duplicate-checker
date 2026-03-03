<?php

declare(strict_types=1);

/*
 * This file is part of the package mfd/typo3-fal-checker.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Mfd\Fal\DuplicateChecker\Controller\Backend;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController as BaseController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsController]
class ElementInformationController extends BaseController
{
    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     * @throws Exception
     * @throws ResourceDoesNotExistException
     */
    #[\Override]
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $view = $this->moduleTemplateFactory->create($request);
        $view->getDocHeaderComponent()->disable();
        $queryParams = $request->getQueryParams();
        $this->table = $queryParams['table'] ?? null;
        $uid = $queryParams['uid'] ?? '';
        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        // Determines if table/uid point to database record or file and if user has access to view information
        $accessAllowed = false;
        if (isset($GLOBALS['TCA'][$this->table])) {
            $uid = (int)$uid;
            // Check permissions and uid value:
            if ($uid && $backendUser->check('tables_select', $this->table)) {
                if ((string)$this->table === 'pages') {
                    $this->row = BackendUtility::readPageAccess($uid, $permsClause) ?: [];
                    $accessAllowed = $this->row !== [];
                } else {
                    $this->row = BackendUtility::getRecordWSOL($this->table, $uid);
                    if ($this->row) {
                        if (isset($this->row['_ORIG_uid'])) {
                            // Make $uid the uid of the versioned record, while $this->row['uid'] is live record uid
                            $uid = (int)$this->row['_ORIG_uid'];
                        }

                        $pageInfo = BackendUtility::readPageAccess((int)$this->row['pid'], $permsClause) ?: [];
                        $accessAllowed = $pageInfo !== [];
                    }
                }
            }
        } elseif (in_array($this->table, ['_FILE', '_FOLDER', 'sys_file'], true)) {
            $fileOrFolderObject = $this->resourceFactory->retrieveFileOrFolderObject($uid);
            if ($fileOrFolderObject instanceof Folder) {
                $this->folderObject = $fileOrFolderObject;
                $accessAllowed = $this->folderObject->checkActionPermission('read');
                $this->type = 'folder';
            } elseif ($fileOrFolderObject instanceof File) {
                $this->fileObject = $fileOrFolderObject;
                $accessAllowed = $this->fileObject->checkActionPermission('read');
                $this->type = 'file';
                $this->table = 'sys_file';
                $this->row = BackendUtility::getRecordWSOL($this->table, $fileOrFolderObject->getUid());
            }
        }

        // Rendering of the output via fluid
        $view->assign('accessAllowed', $accessAllowed);
        $view->assign('hookContent', '');
        if (!$accessAllowed) {
            return $view->renderResponse('ContentElement/ElementInformation');
        }

        // render type by user func
        $typeRendering = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] ?? [];
        foreach ($typeRendering as $className) {
            $typeRenderObj = GeneralUtility::makeInstance($className);
            $hasIsValid = method_exists($typeRenderObj, 'isValid');
            $hasRender = method_exists($typeRenderObj, 'render');
            if ($hasIsValid && $hasRender && $typeRenderObj->isValid($this->type, $this)) {
                $view->assign('hookContent', $typeRenderObj->render($this->type, $this));
                return $view->renderResponse('ContentElement/ElementInformation');
            }
        }

        $pageTitle = $this->getPageTitle();
        $view->setTitle($pageTitle['table'] . ': ' . $pageTitle['title']);
        $view->assignMultiple($pageTitle);
        $view->assignMultiple($this->getPreview($request));
        $view->assignMultiple($this->getPropertiesForTable());
        $view->assignMultiple($this->getReferences($request, $uid));
        $view->assign('returnUrl', GeneralUtility::sanitizeLocalUrl($request->getQueryParams()['returnUrl'] ?? ''));
        $view->assign('maxTitleLength', $this->getBackendUser()->uc['titleLen'] ?? 20);

        $view->assign('duplicates', []);
        if ($this->table === 'sys_file') {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file');
            $duplicates = $queryBuilder->select('*')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq(
                        'sha1',
                        $queryBuilder->createNamedParameter($this->row['sha1'])
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->neq(
                        'uid',
                        $queryBuilder->createNamedParameter($this->row['uid'])
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();

            $view->assign('duplicates', $duplicates);
        }

        return $view->renderResponse('ContentElement/ElementInformation');
    }
}
