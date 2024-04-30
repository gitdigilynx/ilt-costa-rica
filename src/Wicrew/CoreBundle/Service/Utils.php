<?php

namespace App\Wicrew\CoreBundle\Service;

use App\Wicrew\SystemConfigurationBundle\Entity\SystemConfiguration;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Utils
 */
class Utils {

    /**
     * Container interface
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Entity manager
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Translator interface
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Request stack
     *
     * @var RequestStack
     */
    protected $request;

    /**
     * Filesystem
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Session
     *
     * @var Session
     */
    protected $session;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param Filesystem $filesystem
     * @param SessionInterface $session
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager, TranslatorInterface $translator, Filesystem $filesystem, SessionInterface $session) {
        $this->setContainer($container);
        $this->setEntityManager($entityManager);
        $this->setTranslator($translator);
        $this->setFilesystem($filesystem);
        $this->setSession($session);
    }

    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Set container
     *
     * @param ContainerInterface $container
     *
     * @return Utils
     */
    public function setContainer(ContainerInterface $container): Utils {
        $this->container = $container;
        return $this;
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager {
        return $this->entityManager;
    }

    /**
     * Set entity manager
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return Utils
     */
    public function setEntityManager(EntityManagerInterface $entityManager): Utils {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Get translator
     *
     * @return TranslatorInterface
     */
    public function getTranslator() {
        return $this->translator;
    }

    /**
     * Set Translator
     *
     * @param TranslatorInterface $translator
     *
     * @return Utils
     */
    public function setTranslator(TranslatorInterface $translator): Utils {
        $this->translator = $translator;
        return $this;
    }

    /**
     * Get filesystem
     *
     * @return Filesystem
     */
    public function getFilesystem() {
        return $this->filesystem;
    }

    /**
     * Set filesystem
     *
     * @param Filesystem $filesystem
     *
     * @return Utils
     */
    public function setFilesystem(Filesystem $filesystem): Utils {
        $this->filesystem = $filesystem;
        return $this;
    }

    /**
     * Get session
     *
     * @return SessionInterface
     */
    public function getSession(): SessionInterface {
        return $this->session;
    }

    /**
     * Set session
     *
     * @param SessionInterface $session
     *
     * @return Utils
     */
    public function setSession(SessionInterface $session): Utils {
        $this->session = $session;
        return $this;
    }

    /**
     * Get RequestStack
     *
     * @return RequestStack
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Set RequestStack
     *
     * @param RequestStack $request
     *
     * @return Utils
     */
    public function setRequest(RequestStack $request): Utils {
        $this->request = $request;
        return $this;
    }

    /**
     * Record logging
     *
     * @param string $user
     * @param string $entity
     * @param string $action
     * @param string $customMessage
     */
    public function recordLogging($user = null, $entity, $entityClass, $action, $customMessage = null, $changedValues = null) {
        $em = $this->getEntityManager();
        $logging = new Logging;
        $logging->setUser($user);
        $logging->setEntity($entityClass);
        $logging->setEntityId(is_numeric($entity) ? $entity : $entity->getId());
        $translated = $this->getTranslator()->trans('core.entity');
        $entityPath = explode("\\", $entityClass);
        $message = $action . ' ' . $translated . ' ' . $entityPath[count($entityPath) - 1] . ' ' . $customMessage;
        $logging->setActions($message);
        $logging->setChangedValues($changedValues);

        $em->persist($logging);
        $em->flush();

        //        $rawQuery = 'INSERT INTO Logging (user_id, entity, entity_id, actions, changed_values, created_date) VALUES(:user, :entity, :entityid, :actions, :changedvalues, :createddate);';
        //        $statement = $em->getConnection()->prepare($rawQuery);
        //        $statement->bindValue('user', $user->getId());
        //        $statement->bindValue('entity', $entityClass);
        //        $statement->bindValue('entityid', is_numeric($entity) ? $entity : $entity->getId());
        //        $statement->bindValue('actions', $message);
        //        $statement->bindValue('changedvalues', $changedValues);
        //        $statement->bindValue('createddate', date('Y-m-d H:i:s'));
        //        $statement->execute();
    }

    /**
     * Save logging custom
     *
     * @param $user
     * @param $entityId
     * @param $entityClass
     */
    public function recordCustomLogging($user = null, $entityId, $entityClass, $message) {
        $em = $this->getEntityManager();
        $logging = new Logging;
        $logging->setUser($user);
        $logging->setEntity($entityClass);
        $logging->setEntityId($entityId);
        $logging->setActions($message);

        $em->persist($logging);
        $em->flush();
    }

    /**
     * Clean file name
     *
     * @param $filename
     */
    public function cleanFileName($filename) {
        $filename = $this->replaceAccents($filename);
        $filename = utf8_encode($filename);
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
        $filename = str_replace('?', '', $filename);
        $filename = str_replace(' ', '_', $filename);
        return $filename;
    }

    /**
     * Replace French accent
     *
     * @param $filename
     */
    function replaceAccents($filename) {
        $search = explode(",", "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,Ø,Å,Á,À,Â,Ä,È,É,Ê,Ë,Í,Î,Ï,Ì,Ò,Ó,Ô,Ö,Ú,Ù,Û,Ü,Ÿ,Ç,Æ,Œ");
        $replace = explode(",", "c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,o,O,A,A,A,A,A,E,E,E,E,I,I,I,I,O,O,O,O,U,U,U,U,Y,C,AE,OE");

        return str_replace($search, $replace, $filename);
    }

    /**
     * Download file
     *
     * @param $file
     */
    public function downloadFile($basedir, $file) {
        $file = base64_decode($file);
        $file = $basedir . $file;
        if (file_exists($file)) {
            $response = new Response();
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', mime_content_type($file));
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($file) . '";');
            $response->headers->set('Content-length', filesize($file));
            $response->sendHeaders();
            $response->setContent(file_get_contents($file));
            echo $response;
            exit();
        }
    }

    /**
     * Download zip file
     *
     * @param $file
     */
    public function downloadZipFile($basedir, $file) {
        $file = base64_decode($file);
        $file = $basedir . $file;
        if (file_exists($file)) {
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . filesize($file));
            ob_end_flush();
            @readfile($file);
            exit();
        }
    }

    /**
     * Check label of value
     *
     * @param data $data
     * @param entity $entity
     * @param key $key
     *
     * @return string
     */
    public function checkTranslateLabel($data, $entity, $key, $id = null) {
        if (strlen($data)) {
            //$entiryClass = $this->getEntityManager()->getRepository($entity)->findOneBy(array('id' => $id));
            $entiryClass = new $entity;
            if (strpos($key, '.')) {
                $keys = explode('.', $key);
                $keyProperty = $keys[0];
                $keyTarget = $keys[1];
            } else {
                $keyProperty = $key;
                $keyTarget = $key;
            }
            $getKey = 'get' . ucfirst($keyProperty);
            $methodName = 'getLabel' . ucfirst($keyProperty);
            if (method_exists($entiryClass, $methodName)) {
                $tranData = $entiryClass->$methodName($data);
                if (is_array($tranData)) {
                    $newlabel = [];
                    foreach ($tranData as $label) {
                        $newlabel[] = $this->translator->trans($label);
                    }
                    return implode(',', $newlabel);
                } else {
                    return $this->translator->trans($tranData);
                }
            }
        }

        return $data;
    }

    /**
     * Check JSON type data
     *
     * @param string $str
     *
     * @return bool
     */
    function isJson($str) {
        if ((substr($str, 0, 1) != "[" && substr($str, 0, 1) != "{") && (substr($str, -1) != "]" && substr($str, -1) != "}")) {
            return false;
        }
        json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Check existing bundle
     *
     * @param string $bundle
     *
     * @return bool
     */
    public function isBundleExist($bundle) {
        return in_array($bundle, array_keys($this->getContainer()->getParameter('kernel.bundles')));
    }

    /**
     * Get configuration value from full path
     *
     * @param type $key
     *
     * @return string
     */
    public function getSystemConfigValue($key) {
        $em = $this->getEntityManager();
        return $em->getRepository(SystemConfiguration::class)->getConfigValue($key);
    }

    /**
     * Get configuration values by group path
     *
     * @param string $groupPath
     * @param bool $asDimension
     *
     * @return array
     */
    public function getSystemConfigValues($groupPath, $asDimension = false) {
        $em = $this->getEntityManager();
        return $em->getRepository(SystemConfiguration::class)->getValuesByGroupPath($groupPath, $asDimension);
    }

    /**
     * Get Parameter value
     *
     * @param string $parameterName
     */
    public function getParameterValue($parameterName) {
        return $this->getContainer()->getParameter($parameterName);
    }

    /**
     * @param string $timeStr
     *
     * @return DateTime|null
     */
    public function strToTimeNoDefault(string $timeStr): ?DateTime {
        try {
            if ($timeStr !== "") {
                $retTime = new DateTime($timeStr);
            } else {
                $retTime = null;
            }
        } catch (Exception $e) {
            $retTime = null;
        }

        return $retTime;
    }

    public function pricesIncludeTax(): bool {
        return $_ENV['USE_TAX'] === "true";
    }

    public function generateGoogleMapsDropdown(string $destInputId, string $googlePlaceIdInputId): array {
        return [
            'destInputId' => $destInputId,
            'googlePlaceIdInputId' => $googlePlaceIdInputId,
            'mapSize' => [
                'width' => "100%",
                'height' => "300px"
            ],
            'showMap' => false
        ];
    }

    /**
     * Checks if the request contains an order ID in the query.
     * Otherwise removes one if the session has one.
     * This prevents starting an editing session, leaving it prematurely, and having the edit session persist when you try to make a new order normally.
     *
     * @param Request $request
     */
    public function checkForOrderEditSession(Request $request): void {
        $session = $request->getSession();
        $session->remove('orderID'); // Remove any pre-existing order ID regardless of the outcome of this function.

        if ($request->query->has('orderID')) {
            if ($this->getContainer()->get('security.token_storage')->getToken() !== null) {
                $authHelper = $this->getContainer()->get('security.authorization_checker');
                $isRoleAdmin = $authHelper->isGranted('ROLE_EMPLOYEE');
                if (!$isRoleAdmin) {
                    return;
                }
            }
            $session->set('orderID', $request->query->get('orderID'));
        }
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function duplicateVichFile(string $filePath): string {
        $directory = pathinfo($filePath, PATHINFO_DIRNAME);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // If this file was already a duplicate then remove the dupe suffix.
        $length = strlen($fileName);
        $secondToLastChar = $fileName . (strlen($fileName) - 2);
        $lastChar = $fileName. (strlen($fileName) - 1);
        if ($secondToLastChar === "_" && is_numeric($lastChar)) {
            $fileName = substr($fileName, 0, $length - 2);
        }

        $index = 1;
        do {
            $suffix = "_$index";
            $newFilePath = "$directory/$fileName$suffix.$extension";
            $index++;
        } while (is_file($newFilePath));

        $this->getFilesystem()->copy($filePath, $newFilePath, true);
        return pathinfo($newFilePath, PATHINFO_BASENAME);
    }

    public function encrypt_decrypt($string, $action = 'encrypt')
    {
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'AA74CDCC2BBRT935136HH7B63C622'; // user define private key
        $secret_iv = '5fgf5HJ5g622'; // user define secret key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }
}
