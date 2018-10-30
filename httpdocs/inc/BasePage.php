<?php
require_once SMARTY_DIR . '/Smarty.class.php';

class BasePage
{

    public $page_template = "basepage.tpl";
    public $smarty = '';
    public $userdata = '';
    public $galleries = '';
    public $page = '';
    public $description = '';
    public $keywords = '';

    function Page()
    {
        @session_start();
        session_cache_limiter('public');

        $this->smarty = new Smarty();
        $this->smarty->force_compile = True;
        $this->smarty->caching = False;
        $this->smarty->cache_lifetime = 240;
        $this->smarty->setCompileDir(SMARTY_DIR . '/templates_c/');
        $this->smarty->setConfigDir(SMARTY_DIR . '/configs/');
        $this->smarty->setCacheDir(SMARTY_DIR . '/cache/');
        $this->smarty->error_reporting = (E_ALL);
        $this->smarty->debugging = False;

        $this->page = isset($_GET['page']) ? $_GET['page'] : 'api';
    }

    public function render()
    {
        $this->smarty->assign('page', $this);
        // $this->smarty->loadFilter("output", "trimwhitespace");
        $this->smarty->display($this->page_template);
    }

    public function isPostBack()
    {
        return (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST');
    }

    function show403($from_admin = False)
    {
        $redirect_path = ($from_admin) ? str_replace(DS . 'admin', '', WWW_TOP) : WWW_TOP;
        if (strlen($_SERVER['REQUEST_URI']) != 1)
            header('Location: ' . $redirect_path . DS . 'login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        else
            header('Location: ' . $redirect_path . DS . 'login');
        die();
    }

    public function redirect($url){
        if($url){
            header('Location: ' . $url);
        } else {
            header('Location: /');
        }
        die();
    }

    public function logUserView(){
        $user = new Users();
        if($user->isLoggedIn()){
            $user->updateLastSeen($user->currentUserID());
        }
    }

}

?>
