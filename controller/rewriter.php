<?php
/**
 *
 * @package Simple SEO
 * @copyright (c) 2013 Carlo (carlino1994/carlo94it)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace carlo94it\simpleseo\controller;

/**
 * Class settings
 *
 * @package carlo94it\simpleseo\controller
 */
class rewriter
{
	/**
	 * Database object
	 * @var \phpbb\db\driver\driver
	 */
	protected $db;

	/**
	 * Request object
	 * @var \phpbb\request\request
	 */
	protected $request;

	/**
	 * phpBB root path
	 * @var string
	 */
	protected $root_path;

	/**
	 * phpEx
	 * @var string
	 */
	protected $php_ext;

	/**
	 * Controller helper object
	 * @var \phpbb\controller\helper
	 */
	protected $helper;

	/**
	 * Regular Expressions
	 * @var array
	 */
	protected $reg_exp;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver				$db			Database object
	 * @param \phpbb\request\request				$request	Request object
	 * @param string								$root_path	phpBB root path
	 * @param string								$php_ext	phpEx
	 * @return \carlo94it\simpleseo\controller\rewriter
	 * @access public
	 */
	public function __construct(\phpbb\db\driver\driver $db, \phpbb\request\request $request, \phpbb\controller\helper $helper, $root_path, $php_ext)
	{
		$this->db = $db;
		$this->request = $request;
		$this->helper = $helper;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->reg_exp = array(
			'find'		=> array(
				'`&([a-z]+)(acute|grave|circ|cedil|tilde|uml|lig|ring|caron|slash);`i',
				'`&(amp;)?[^;]+;`i',
				'`[^a-z0-9]`i',
				'`(^|-)[a-z0-9]{1,2}(?=-|$)`i',
				'`[-]+`',
			),
			'replace'	=> array(
				'\1',
				'-',
				'-',
				'-',
				'-',
			),
		);
	}

	public function generate_url($params, $type)
	{
		if ($type == 'forum')
		{
			$sql = 'SELECT forum_id, forum_name
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . $params['forum_id'];
		}
		else if ($type == 'topic')
		{
			$sql = 'SELECT t.topic_id, t.topic_title, f.forum_id, f.forum_name
				FROM
					' . FORUMS_TABLE . ' f,
					' . TOPICS_TABLE . ' t
				WHERE
					t.topic_id = ' . $params['topic_id'] . ' AND f.forum_id = t.forum_id';
		}
		else if ($type == 'post')
		{
			$sql = 'SELECT p.post_id, t.topic_id, t.topic_title, f.forum_id, f.forum_name
				FROM
					' . FORUMS_TABLE . ' f,
					' . TOPICS_TABLE . ' t,
					' . POSTS_TABLE . ' p
				WHERE
					p.post_id = ' . $params['post_id'] . ' AND t.topic_id = p.topic_id AND f.forum_id = t.forum_id';
		}

		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($type == 'forum')
		{
			return $this->helper->route('simpleseo_url_forum', array(
				'forum_name'	=> $this->format_title_url($row['forum_name'], 'forum'),
				'forum_id'		=> (int) $row['forum_id'],
			));
		}
		else if ($type == 'topic' || $type == 'post')
		{
			return $this->helper->route('simpleseo_url_topic', array(
				'forum_name'	=> $this->format_title_url($row['forum_name'], 'forum'),
				'forum_id'		=> (int) $row['forum_id'],
				'topic_title'	=> $this->format_title_url($row['topic_title'], 'topic'),
				'topic_id'		=> (int) $row['topic_id'],
			));
		}
	}

	protected function format_title_url($title, $type = '')
	{
		$title = utf8_recode($title, 'iso-8859-1');
		$title = utf8_normalize_nfc($title);
		$title = preg_replace('`\[.*\]`U','', $title);
		$title = htmlentities($title, ENT_COMPAT, 'UTF-8');
		$title = preg_replace($this->reg_exp['find'], $this->reg_exp['replace'], $title);
		$title = strtolower(trim($title, '-'));

		if (!empty($type))
		{
			if ($type == 'forum')
			{
				$title .= '-f';
			}

			if ($type == 'topic')
			{
				$title .= '-t';
			}
		}

		return $title;
	}
}
