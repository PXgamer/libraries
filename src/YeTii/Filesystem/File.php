<?php
namespace YeTii\FileSystem;

use YeTii\General\Str;

class File {

	protected $exists;
	protected $is_dir;
	protected $has_children;

	protected $name;
	protected $full_path;
	protected $extension;

	protected $date_modified;

	protected $children_count;

	protected $children;
	protected $parent;

	protected $settings = [
		'show_hidden_files'=>true
	];

	public function __construct(string $path, array $settings = []) {
		$this->full_path = rtrim($path, '/');
		$this->settings = array_merge($this->settings, $settings);
		$this->init();
	}

	public function __get($name) {
		if (in_array($name, ['children','parent']))
			return $this->{$name}();
	}

	public function __set($name, $val) {
		$this->{$name} = $val;
	}

	private function init() {
		foreach (['name','extension','date_modified','children_count','children','parent'] as $key)
			$this->{$key} = null;
		$this->exists = file_exists($this->full_path)?1:0;
		$this->name = Str::afterLast($this->full_path, '/')->value;
		if ($this->exists) {
			$this->is_dir = is_dir($this->full_path)?1:0;
			if (!$this->is_dir) {
				$this->extension = Str::getExtension($this->name, '');
			}else{
				$this->children_count = 0;
				foreach (scandir($this->full_path) as $f)
					if ($f!='.'&&$f!='..'&& ($this->settings['show_hidden_files']||$f[0]!='.'))
						$this->children_count++;
				$this->has_children = $this->children_count?1:0;
			}
		}
	}

	private function children() {
		if (!$this->is_dir)
			return false;
		$children = [];
		foreach (scandir($this->full_path) as $f) {
			if ($f=='.'||$f=='..') continue;
			if ($this->settings['show_hidden_files']||$f[0]!='.')
				$children[] = new File($this->full_path.'/'.$f, $this->settings);
		}
		$this->children = $children;
		$this->children_count = count($children); // updates these
		$this->has_children = count($children)?1:0; // updates these
		return $children;
	}

	private function parent() {
		$path = Str::beforeLast($this->full_path, '/');
		if ($path) {
			$this->parent = new File($path, $this->settings);
			return $this->parent;
		}else{
			return false;
		}
	}

	public function delete() {
		$this->filedelete($this->full_path);
	}

	private function filedelete($filename) {
		if (is_dir($filename)) {
			foreach (scandir($filename) as $f) {
				if ($f=='.'||$f=='..') continue;
				$this->filedelete($filename);
			}
		}else{
			unlink($filename);
		}
	}

	public function rename($to, $keepExt = false) {
		$to = $this->parent->full_path.'/'.$to.($keepExt?".{$this->extension}":'');
		if (file_exists($to))
			return false;
		rename($this->full_path, $to);
		$this->init();
		return $this;
	}

	public function move($to) {
		if (file_exists($to))
			return false;
		rename($this->full_path, $to);
		$this->init();
		return $this;
	}

	// public function breadcrumbs() {
	// 	$crumbs = [];
	// 	$str = new Str($this->full_path);
	// 	$pre = $str->beforeFirst('/');		
	// 	return $crumbs;
	// }

}