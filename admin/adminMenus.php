<?php

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
	abstract protected function render();

	/**
	 * Renders children elements
	 */
	protected function renderChildren() {
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
	protected function matchSlug($slug) {
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
	abstract protected function renderBreadCrumbs($extra = null);

	/**
	 * Renders portion of page title for this menu element
	 *
	 * @param string $extra Extra item to be appended at the end
	 */
	protected function renderTitle($extra = null) {
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

	protected function matchSlug($slug) {
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

/**
 * Menu section that holds multiple menus
 *
 * @package StartupAPI
 * @subpackage Admin
 */
class MenuSection extends MenuElement {

	protected function render() {
		?><li class="nav-header"> <?php echo $this->title ?></li><?php
		$this->renderChildren();
		?><li class="divider"></li><?php
	}

	public function renderTopNav() {
		$enabled = false;
		foreach ($this->sub_menus as $menu) {
			if ($menu->enabled) {
				$enabled = true;
			}
		}
		$this->enabled = $enabled;

		if (!$this->enabled) {
			return;
		}

		if ($this->enabled) {
			if (is_null($this->sub_menus)) {
				$link = $this->link;
			} else if (count($this->sub_menus) > 0) {
				$link = $this->sub_menus[0]->link;
			} else {
				return;
			}
			?>
			<li<?php if ($this->active) { ?> class="active"<?php } ?>>
				<?php if (!is_null($link)) { ?>
					<a href="<?php echo $link ?>">
						<?php echo $this->title ?>
					</a>
					<?php
				} else {
					echo $this->title;
				}
				?>
			</li>
			<?php
		} else {
			?>
			<li class="disabled">
				<a href="#" class="disabled">
					<?php echo $this->title; ?>
				</a>
			</li>
			<?php
		}
	}

	protected function renderBreadCrumbs($extra = null) {
		if ($this->active) {
			if (is_null($this->sub_menus)) {
				$link = $this->link;
			} else if (count($this->sub_menus) > 0) {
				$link = $this->sub_menus[0]->link;
			}
			?>
			<li>
				<?php if (!is_null($link)) { ?>
					<a href="<?php echo $link ?>">
						<?php if (!is_null($this->icon)) { ?><i class="icon-<?php echo $this->icon ?>"></i><?php } ?>
						<?php echo $this->title ?>
					</a>
					<?php
				} else {
					if (!is_null($this->icon)) {
						?>
						<i class="icon-<?php echo $this->icon ?>"></i>
						<?php
					}
					echo $this->title;
				}

				if (is_array($this->sub_menus)) {
					?><span class="divider">/</span><?php
			}
				?>
			</li>
			<?php
			foreach ($this->sub_menus as $menu) {
				$menu->renderBreadCrumbs($extra);
			}
		}
	}

}

/**
 * Leaf menu item without subitems
 *
 * @package StartupAPI
 * @subpackage Admin
 */
class Menu extends MenuElement {

	/**
	 * Creates a leaf menu item
	 *
	 * @param string $slug Slug
	 * @param string $title Menu title
	 * @param string $link Menu link
	 * @param string $icon Twitter Bootstrap icon slug
	 * @param boolean $enabled Enabled / disabled
	 * @param string $disabled_message Message to display in tooltip on disabled menu
	 */
	public function __construct($slug, $title, $link, $icon = null, $enabled = true, $disabled_message = 'Coming soon') {
		parent::__construct($slug, $title, $link, null, $icon, $enabled, $disabled_message);
	}

	protected function render() {
		if (is_null($this->link)) {
			return;
		}
		if ($this->enabled) {
			?>
			<li<?php if ($this->active) { ?> class="active"<?php } ?>>
				<a href="<?php echo $this->link ?>">
					<?php if (!is_null($this->icon)) { ?><i class="icon-<?php echo $this->icon ?>"></i><?php } ?>
					<?php echo $this->title ?>
				</a>
			</li>
			<?php
		} else {
			?>
			<li class="disabled"<?php if (!is_null($this->disabled_message)) { ?> title="<?php echo $this->disabled_message ?>"<?php } ?>>
				<a href="#">
					<?php if (!is_null($this->icon)) { ?><i class="icon-<?php echo $this->icon ?>"></i><?php } ?>
					<?php echo $this->title ?>
				</a>
			</li>
			<?php
		}
	}

	public function renderTopNav() {
		if (!$this->enabled) {
			return;
		}
		?>
		<li<?php if ($this->active) { ?> class="active"<?php } ?>>
			<a href="<?php echo $this->link ?>">
				<?php echo $this->title ?>
			</a>
		</li>
		<?php
	}

	protected function renderBreadCrumbs($extra = null) {
		if ($this->active) {
			if (is_null($extra)) {
				?><li class="active"><?php echo $this->title ?></li><?php
			} else {
				if (is_null($this->sub_menus)) {
					$link = $this->link;
				} else if (count($this->sub_menus) > 0) {
					$link = $this->sub_menus[0]->link;
				}
				?>
				<li class="active">
					<?php if (!is_null($link)) { ?>
						<a href="<?php echo $link ?>">
							<?php echo $this->title ?>
						</a>
						<?php
					} else {
						echo $this->title;
					}
					?>
				</li>
				<?php
			}
		}
	}

}
