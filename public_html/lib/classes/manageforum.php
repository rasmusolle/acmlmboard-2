<?php
class ManageForum {
	private $sql = null;
	
	public function __construct($sql) {
		$this->sql = $sql;
	}
	
	public function createCategory($title, $order = 0, $private = 0) {
		$cid = $this->sql->resultq("SELECT MAX(id) FROM categories");
		if (!$cid) {
			$cid = 0;
		}
		$cid++;
		$this->sql->prepare("INSERT INTO categories (id, title, ord, private) VALUES (?, ?, ?, ?)", array($cid, $title, $order, $private));
	}
	
	public function createForum($category_id, $title, $description, $order = 0, $private = 0, $trash = 0, $readonly = 0, $announcechan_id = 0) {
		$fid = $sql->resultq("SELECT MAX(id) FROM `forums`");
		if (!$fid) {
			$fid = 0;
		}
		$fid++;
		$this->sql->prepare("INSERT INTO forums (id, cat, title, descr, ord, private, trash, readonly, announcechan_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
				array($fid, $cat, $title, $descr, $ord, $private, $trash, $readonly, $announcechan_id));
	}
	
	public function createChannel($channel_name) {
		
	}

	public function updateCategory($category_id, $title, $order = 0, $private = 0) {
	}
	
	public function updateForum($forum_id, $category_id, $title, $description, $order = 0, $private = 0, $trash = 0, $readonly = 0, $announcechan_id = 0) {
	}
	
	public function updateChannel($channel_id, $channel_name) {
		if (!$this->sql->resultp("SELECT COUNT(*) FROM announcechans WHERE id=?", array($channel_id))) {
			throw new Exception('Channel not found.');
		}
		
		$this->sql->prepare("UPDATE announcechans SET chan=? WHERE id=?", array($channel_id, $channel_name));
		if (!$this->sql->affectedrows()) {
			throw new Exception('There was a problem updating the channel.');
		}

		return true;
	}
	
	public function deleteCategory($category_id) {
		$sql->prepare("DELETE FROM categories WHERE id=?", array($category_id));
	}
	
	public function deleteForum($forum_id) {
		$this->sql->prepare("DELETE FROM forums WHERE id=?", array($forum_id));
		$this->sql->prepare("DELETE FROM forummods WHERE fid=?", array($forum_id));
		$this->sql->prepare("DELETE FROM tags WHERE fid=?", array($forum_id));
	}
	
	public function deleteChannel($channel_id) {
		$this->sql->prepare("UPDATE forums SET announcechan_id=? WHERE announcechan_id=?", array('0', $channel_id));
		$this->sql->prepare("DELETE FROM announcechans WHERE id=?", array($channel_id));
	}
}