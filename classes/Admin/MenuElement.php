<?php
namespace StartupAPI\Admin;

/**
 * Abstract class representing the menu hierarchy elements
 *
 * Subclasses will represent particular levels of this hierarchy that behave differently
 *
 * @package StartupAPI
 * @subpackage Admin
 *
 * @todo Add disabling sections of menus altogether
 */
abstract class MenuElement {

	/**
	 * @var string String that separates menu sections in the title of the page
	 */
	protected static $titleSeparator = ' / ';

	/**
	 * @var string Menu slug to be used to identify this particular menu element
	 */
	protected $slug;

	/**
	 * @var string Menu element display name
	 */
	protected $title;

	/**
	 * @var string Menu link for those elements that are clickable
	 */
	protected $link;

	/**
	 * @var MenuElement[] An array of child elements for elements that support children
	 */
	protected $sub_menus = array();

	/**
	 * @var string Icon slug
	 */
	protected $icon;

	/**
	 * @var boolean Whatever menu item is currently selected
	 */
	protected $active;

	/**
	 * @var boolean Whatever menu item is enabled or disabled
	 */
	protected $enabled;

	/**
	 * @var string Message to display if menu item is disabled
	 */
	protected $disabled_message;

	/**
	 * Creates a menu item
	 *
	 * Subclasses can call this constructor with subset of parameters they support
	 *
	 * @param string $slug Slug
	 * @param string $title Menu title
	 * @param string $link Menu link
	 * @param MenuElement[] $sub_menus
	 * @param string $icon Twitter Bootstrap icon slug
	 * @param boolean $enabled Enabled / disabled
	 * @param string $disabled_message Message to display in tooltip on disabled menu
	 */
	public function __construct($slug, $title, $link, $sub_menus = null, $icon = null, $enabled = true, $disabled_message = 'Coming soon') {
		$this->slug = $slug;
		$this->title = $title;
		$this->link = $link;
		$this->sub_menus = $sub_menus;
		$this->icon = $icon;
		$this->enabled = $enabled;
		$this->disabled_message = $disabled_message;
	}

	/**
	 * Renders menu item in HTML
	 *
	 * Implementations should render their own tags and call submenu's
	 * renderChildren() function where supported
	 */
	abstract public function render();

	/**
	 * Renders children elements
	 */
	public function renderChildren() {
		foreach ($this->sub_menus as $menu) {
			$menu->render();
		}
	}

	/**
	 * Sets currently active menu elements based on a slug
	 *
	 * @param string $active_slug Slug of active menu element
	 *
	 * @return boolean Returns true if child is active so parent can also set itself to active state
	 */
	public function setActive($active_slug) {
		if ($this->matchSlug($active_slug)) {
			$this->active = true;

			return true;
		} else if (is_array($this->sub_menus)) {
			foreach ($this->sub_menus as $menu) {
				$this->active = $menu->setActive($active_slug);

				if ($this->active) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Compares passed slug to this menu element's slug
	 *
	 * Primarily to be overriden in the root element that is not supposed to be matched
	 *
	 * @param string $slug Slug to match
	 *
	 * @return boolean Returns true if slugs match
	 */
	public function matchSlug($slug) {
		return $this->slug == $slug;
	}

	/**
	 * Renders top navigation bar element for this menu item
	 */
	public function renderTopNav() {

	}

	/**
	 * Renders breadcrumbs for the node
	 *
	 * @param string $extra Extra string to attach to the end of breadcrumbs
	 */
	abstract public function renderBreadCrumbs($extra = null);

	/**
	 * Renders portion of page title for this menu element
	 *
	 * @param string $extra Extra item to be appended at the end
	 */
	public function renderTitle($extra = null) {
		if ($this->active) {
			echo self::$titleSeparator . $this->title;
		}

		if (!is_null($this->sub_menus) && is_array($this->sub_menus) && count($this->sub_menus) > 0) {
			foreach ($this->sub_menus as $menu) {
				$menu->renderTitle($extra);
			}
		}
	}

}
