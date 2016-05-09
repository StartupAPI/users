<?php
namespace StartupAPI\Admin;

/**
 * Menu section that holds multiple menus
 *
 * @package StartupAPI
 * @subpackage Admin
 */
class MenuSection extends MenuElement {

	public function render() {
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

	public function renderBreadCrumbs($extra = null) {
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
