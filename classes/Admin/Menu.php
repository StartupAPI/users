<?php
namespace StartupAPI\Admin;

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

	public function render() {
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

	public function renderBreadCrumbs($extra = null) {
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
