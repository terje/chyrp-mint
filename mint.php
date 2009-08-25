<?php

  class Mint extends Modules {

    private $birdfeeder;

    public function head() {
      $config = Config::current();
      # Insert the Mint javascript
      echo '<script src="' . $config->mint_path . '/?js" type="text/javascript"></script>';
    }

    public function feed_url($url, $post) {
      $config = Config::current();
      if ($config->enable_birdfeeder) {
        # Modify the post url to point to the birdfeeder.  That way clicks from RSS can be tracked.
        $url = $this->get_birdfeeder()->seed($post->title(), $url, true);
      }
    }

    public function settings_nav($navs) {
      if (Visitor::current()->group->can("change_settings"))
        $navs["mint_settings"] = array("title" => __("Mint", "mint"));

      return $navs;
    }

    public function admin_mint_settings($admin) {
      if (!Visitor::current()->group->can("change_settings"))
        show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

      if (empty($_POST))
        return $admin->display("mint_settings");

      if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
        show_403(__("Access Denied"), __("Invalid security key."));

      $mint_feed_title = $_POST['mint_feed_title'];

      $config = Config::current();

      $set = array(
        $config->set("mint_path", $_POST['mint_path']),
        $config->set("mint_feed_title", $_POST['mint_feed_title']),
        $config->set('enable_birdfeeder', isset($_POST['enable_birdfeeder']))
      );

      if (!in_array(false, $set))
        Flash::notice(__("Settings updated."), "/admin/?action=mint_settings");

    }

    protected function get_birdfeeder() {
      # Check if the birdfeeder has already been included to prevent it from being included
      # more than once in this module's lifetime
      if (!isset($this->birdfeeder)) {
        # Set a feed title from the config
        $config = Config::current();
        define('BIRDFEED', $config->mint_feed_title);

        # Include the Mint Bird Feeder pepper.  This will increment the feed counter in Mint.
        # The global here is to avoid missing Mint objects in the include
        global $Mint;
        include($_SERVER['DOCUMENT_ROOT'] . '/feeder/index.php');

        # Preserve the BirdFeeder object, we'll need it later and don't want to include the above
        # file again since it increments the counter
        $this->birdfeeder = $BirdFeeder;
      }

      return $this->birdfeeder;
    }

    static function __install() {
      $config = Config::current();
      $config->set('mint_path', '/mint');
      $config->set('mint_feed_title', 'Main Feed');
      $config->set('enable_birdfeeder', true);
    }

    static function __uninstall() {
      $config = Config::current();
      $config->remove('mint_path');
      $config->remove('mint_feed_title');
      $config->remove('enable_birdfeeder');
    }
  }

?>
