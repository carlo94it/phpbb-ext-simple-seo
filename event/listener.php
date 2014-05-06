<?php
/**
 *
 * @package Simple SEO
 * @copyright (c) 2013 Carlo (carlino1994/carlo94it)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace carlo94it\simpleseo\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
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
	 * Rewriter object
	 * @var \carlo94it\simpleseo\controller\rewriter
	 */
	protected $rewriter;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver				$db			Database object
	 * @param \phpbb\request\request				$request	Request object
	 * @param string								$root_path	phpBB root path
	 * @param string								$php_ext	phpEx
	 * @return \carlo94it\simpleseo\event\listener
	 * @access public
	 */
	public function __construct(\phpbb\db\driver\driver $db, \phpbb\request\request $request, \phpbb\controller\helper $helper, $root_path, $php_ext)
	{
		$this->db = $db;
		$this->request = $request;
		$this->helper = $helper;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Get subscribed events
	 *
	 * @return array
	 * @static
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'	=> 'setup',
			'core.append_sid'	=> 'dispatch',
		);
	}

	/**
	 * Set up the environment
	 *
	 * @param Event $event Event object
	 * @return null
	 */
	public function setup($event)
	{
		global $phpbb_container;

		$this->container = $phpbb_container;

		$this->rewriter = $this->container->get('carlo94it.simpleseo.rewriter');
	}

	/**
	 * URL Dispatcher
	 *
	 * @param Event $event Event object
	 * @return null
	 */
	public function dispatch($event)
	{
		$path = str_replace($this->root_path, '', $event['url']);

		parse_str(str_replace('&amp;', '&', $event['params']), $params);

		if ($path == 'viewforum.' . $this->php_ext)
		{
			$forum_id = (int) $params['f'];

			$route = $this->rewriter->generate_forum_url($forum_id);
		}

		if ($path == 'viewtopic.' . $this->php_ext)
		{
			if (isset($params['p']))
			{
				$post_id = (int) $params['p'];

				$route = $this->rewriter->generate_post_url($post_id);
			}
			else if (isset($params['t']))
			{
				$topic_id = (int) $params['t'];

				$route = $this->rewriter->generate_topic_url($topic_id);
			}
		}

		if (isset($route))
		{
			$event['append_sid_overwrite'] = str_replace('/app.' . $this->php_ext, '', $route);
		}
	}
}
