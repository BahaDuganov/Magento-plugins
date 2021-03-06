<?php
class Simicart_Simihr_DetailController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $job = 'Simicart Job';
        $benefit = '';
        if(isset($_GET['job']) && $_GET['job'] != '' && isset($_GET['job_type']) && $_GET['job_type'] != '') {
            $job = $_GET['job'];
            $collection = Mage::getResourceModel('simihr/jobOffers_collection')->addFieldToFilter('status', 1)->addFieldToFilter('name', $job)->addFieldToFilter('job_type', $_GET['job_type'])->getData();
            if (isset($collection[0])) {
                $benefit = $collection[0]['benifits'];
            }

        }
        $this->getLayout()->getBlock('head')->setTitle($this->__($job));
        $this->getLayout()->getBlock('head')->setDescription($this->__($benefit));
        $this->getLayout()->getBlock('content')->append($block);
        $this->_initLayoutMessages('core/session'); 
        $this->renderLayout();

        if (isset($_POST['submit'])) {
//            print_r($_POST);
//            die();
            if (isset($_FILES['resume_cv']['name']) && $_FILES['resume_cv']['name'] != '') {
                try {
                    
                    $_FILES['resume_cv']['name'] = self::stripVN($_FILES['resume_cv']['name']);
                    $_FILES['resume_cv']['name'] = str_replace(" ", "_", $_FILES['resume_cv']['name']);
                    $fileName       = $_FILES['resume_cv']['name'];
                    $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                    $fileNamewoe    = rtrim($fileName, $fileExt);
                    // $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
                    $uploader       = new Varien_File_Uploader('resume_cv');
                    $uploader->setAllowedExtensions(array('doc', 'docx','pdf'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);
                    $path = Mage::getBaseDir('media') ;
                    if(!is_dir($path)){
                        mkdir($path, 0777, true);
                    }
                    $uploader->save($path . DS . 'simihr' . DS . 'submissions' . DS, $fileName );
                    $path1 = $path . DS . 'simihr' . DS . 'submissions' . DS. $fileName;
                    $filename1 = $fileName;

                } catch (Exception $e) {
                    $error = true;
                }
            }

            if (isset($_FILES['cover_letter']['name']) && $_FILES['cover_letter']['name'] != '') {
                try {
                    $_FILES['cover_letter']['name'] = self::stripVN($_FILES['cover_letter']['name']);
                    $_FILES['cover_letter']['name'] = str_replace(" ", "_", $_FILES['cover_letter']['name']);
                    $fileName       = $_FILES['cover_letter']['name'];
                    $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
                    $fileNamewoe    = rtrim($fileName, $fileExt);
                    // $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
                    $uploader       = new Varien_File_Uploader('cover_letter');
                    $uploader->setAllowedExtensions(array('doc', 'docx','pdf'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);
                    $path = Mage::getBaseDir('media') ;
                    if(!is_dir($path)){
                        mkdir($path, 0777, true);
                    }
                    $uploader->save($path . DS . 'simihr' . DS . 'submissions' . DS, $fileName );
                    $path2 = $path . DS . 'simihr' . DS . 'submissions' . DS. $fileName;
                    $filename2 = $fileName;
                } catch (Exception $e) {
                    $error = true;
                }

            }

            // add to submission to DB
            $path = Mage::getBaseDir('media') ;
            if(isset($_GET['job'])) {
                $job_applied = $_GET['job'];
            } else {
                $job_applied = '';
            }

            if($_FILES['cover_letter']['name'] != '') {
                $cover_letter_path = $_FILES['cover_letter']['name'];
            } else {
                $cover_letter_path = '';
            }

            $resume_cv_path = $_FILES['resume_cv']['name'];
            $data = array(
                'first_name' => $_POST['first_name'],
                'last_name'  => $_POST['last_name'],
                'email' => $_POST['email'],
                'phone'  => $_POST['phone'],
                'job_applied' => $job_applied,
                'comment'  => $_POST['sourceinfo'],
                'resume_cv_path' => $resume_cv_path,
                'cover_letter_path'  => $cover_letter_path
            );
            $email_applied = $_POST['email'];
            $model = Mage::getModel('simihr/submissions');

            try {
                $model->setData($data)
                ->save();
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            $data = [];
            if (isset($_POST['job_name'])) {
                $data['job_name'] = $_POST['job_name'];
                $data['email_applied'] = $email_applied;
                $data['first_name'] = $_POST['first_name'];
                $data['last_name'] = $_POST['last_name'];
                $data['phone'] =$_POST['phone'];
                $data['sourceinfo'] = $_POST['sourceinfo'];
            }
       
            self::sendMail($data,$title,$path1,$path2,$filename1,$filename2);
            echo "<script>alert('Your submisstion has been send.')</script>";

        }
    }

    public function submitAction()
    {
        
    }

    public function stripVN($str) {
        $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'a', $str);
        $str = preg_replace("/(??|??|???|???|???|??|???|???|???|???|???)/", 'e', $str);
        $str = preg_replace("/(??|??|???|???|??)/", 'i', $str);
        $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'o', $str);
        $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???)/", 'u', $str);
        $str = preg_replace("/(???|??|???|???|???)/", 'y', $str);
        $str = preg_replace("/(??)/", 'd', $str);

        $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'A', $str);
        $str = preg_replace("/(??|??|???|???|???|??|???|???|???|???|???)/", 'E', $str);
        $str = preg_replace("/(??|??|???|???|??)/", 'I', $str);
        $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???|??|???|???|???|???|???)/", 'O', $str);
        $str = preg_replace("/(??|??|???|???|??|??|???|???|???|???|???)/", 'U', $str);
        $str = preg_replace("/(???|??|???|???|???)/", 'Y', $str);
        $str = preg_replace("/(??)/", 'D', $str);
        return $str;
    }

    public function sendMail($data, $title,$path1 = null,$path2 = null,$filename1,$filename2) {
         // Mage::log("Run cron to send mail!");
        $templateId = 182;

        $modelContent = Mage::getResourceModel('simihr/content_collection')->addFieldToFilter('name','transaction_mail_id_submit')->getData();
        if (isset($modelContent[0]) && isset($modelContent[0]['detail'])) {
            $templateId = (int)$modelContent[0]['detail'];
        }
        // get store and config
        $store = Mage::app()->getStore();
        $config = array(
            'area' => 'frontend',
            'store' => $store->getId()
        );

        $sender = array(
            'name' => 'Simihr Notice',
            'email' => 'simihrhr@simicart.com',
        );

        $recipient_email = 'hr@simicart.com';
        $recipient_name = 'hr';

        // add variable
        $vars = array('store' => $store);
        if (sizeof($data) > 0) {
            foreach ($data as $key => $value) {
                $vars[$key] = $value;
            }
        }

        // send transaction email
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        $storeId = Mage::app()->getStore()->getId();

        // $add_cc=array("hieu@simicart.com");
        $mail = Mage::getModel('core/email_template');
        // $mail->getMail()->addCc($add_cc);
        if (file_exists($path1)) {
            $mail->getMail()
                ->createAttachment(
                    file_get_contents($path1),
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    basename($filename1)
                );
        }
        if (file_exists($path2)) {
            $mail->getMail()
                ->createAttachment(
                    file_get_contents($path1),
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    basename($filename2)
                );
        }
        $mail->setDesignConfig($config)
            ->sendTransactional($templateId, $sender, $recipient_email, $recipient_name, $vars, $storeId);
        $translate->setTranslateInline(true);
        Mage::log("Simihr sent mail to hr@simicart.com and max@simicart.com");
    }


    
}