<?php
require_once INC_DIR . '/BasePage.php';


class Page extends BasePage{


    function __construct() {
        parent::Page();

        $this->smarty->addTemplateDir(ROOT . 'theme/templates/');

        $this->page = isset($_GET['page']) ? $_GET['page'] : 'api';

    }

    public function showApiError($errcode=900, $errtext="") {
			$headers = array();
      switch ($errcode) {
        case 100:
          $errtext = "Incorrect user credentials";
					$headers[] = 'HTTP/1.0 403 Forbidden';
          break;
        case 101:
          $errtext = "Account suspended";
					$headers[] = 'HTTP/1.0 403 Forbidden';
          break;
        case 102:
          $errtext = "Insufficient priviledges/not authorized";
					$headers[] = 'HTTP/1.0 403 Forbidden';
          break;
        case 103:
          $errtext = "Registration denied";
					$headers[] = 'HTTP/1.0 403 Forbidden';
          break;
        case 104:
          $errtext = "Registrations are closed";
					$headers[] = 'HTTP/1.0 403 Forbidden';
          break;
        case 105:
          $errtext = "Invalid registration (Email Address Taken)";
          break;
        case 106:
          $errtext = "Invalid registration (Email Address Bad Format)";
          break;
        case 107:
          $errtext = "Registration Failed (Data error)";
          break;
        case 200:
          $errtext = "Missing parameter";
          break;
        case 201:
          $errtext = "Incorrect parameter";
          break;
        case 202:
          $errtext = "No such function";
          break;
        case 203:
          $errtext = "Function not available";
          break;
        case 300:
          $errtext = "No such item";
          break;
        case 500:
          $errtext = "Request limit reached";
          break;
        case 501:
          $errtext = "Download limit reached";
          break;
        default:
          $errtext = "Unknown error";
          break;
    	}
      header("Content-type: text/xml");

			foreach ($headers as $header) {
				header($header);
			}
      echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
      echo "<error code=\"$errcode\" description=\"$errtext\"/>\n";
      die();
    }

    public function renderHTML() {
      $this->smarty->assign('page', $this);
      $this->page_template = 'basepage.tpl';
      parent::render();
    }

    public function render() {
        header("Content-type: text/xml");
        $this->smarty->assign('page', $this);
        $this->page_template = 'basepage.tpl';
        parent::render();
    }

}


?>
