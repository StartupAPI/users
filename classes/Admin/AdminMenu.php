<?php
namespace StartupAPI\Admin;

/**
 * Root element for admin UI menus
 *
 * @package StartupAPI
 * @subpackage Admin
 */
class AdminMenu extends MenuElement {

	/**
	 * Creates a root element for admin menu, accepts only array of subelements
	 *
	 * @param MenuElement[] $sub_menus
	 */
	public function __construct($sub_menus = null) {
		parent::__construct('admin', 'Admin', null, $sub_menus);
	}

	public function render() {
		?><ul class="nav nav-list"><?php
		$this->renderChildren();
		?></ul><?php
	}

	public function matchSlug($slug) {
		return false;
	}

	public function renderTopNav() {
		foreach ($this->sub_menus as $menu) {
			?><ul class="nav"><?php
			$menu->renderTopNav();
			?></ul><?php
		}
	}

	public function renderBreadCrumbs($extra = null) {
		?><ul class="breadcrumb"><?php
		foreach ($this->sub_menus as $menu) {
			$menu->renderBreadCrumbs($extra);
		}

		if (!is_null($extra)) {
			?><li class="active"><span class="divider"> / </span><?php echo $extra ?></li><?php
		}
	}

	public function renderTitle($extra = null) {
		parent::renderTitle($extra);

		if (!is_null($extra)) {
			echo self::$titleSeparator . $extra;
		}
	}

}
