<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Router;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

JLoader::register('FinderIndexerLanguage', __DIR__ . '/language.php');
JLoader::register('FinderIndexerParser', __DIR__ . '/parser.php');
JLoader::register('FinderIndexerToken', __DIR__ . '/token.php');

/**
 * Helper class for the Finder indexer package.
 *
 * @since  2.5
 */
class FinderIndexerHelper
{
	protected static $stemmer_cache = array();

	/**
	 * Method to parse input into plain text.
	 *
	 * @param   string  $input   The raw input.
	 * @param   string  $format  The format of the input. [optional]
	 *
	 * @return  string  The parsed input.
	 *
	 * @since   2.5
	 * @throws  Exception on invalid parser.
	 */
	public static function parse($input, $format = 'html')
	{
		// Get a parser for the specified format and parse the input.
		return FinderIndexerParser::getInstance($format)->parse($input);
	}

	/**
	 * Method to tokenize a text string.
	 *
	 * @param   string   $input   The input to tokenize.
	 * @param   string   $lang    The language of the input.
	 * @param   boolean  $phrase  Flag to indicate whether input could be a phrase. [optional]
	 *
	 * @return  array  An array of FinderIndexerToken objects.
	 *
	 * @since   2.5
	 */
	public static function tokenize($input, $lang, $phrase = false)
	{
		static $cache = [], $tuplecount;
		static $multilingual;
		static $defaultLanguage;

		if (!$tuplecount)
		{
			$params = ComponentHelper::getParams('com_finder');
			$tuplecount = $params->get('tuplecount', 1);
		}

		if (is_null($multilingual))
		{
			$multilingual = Multilanguage::isEnabled();
			$config = ComponentHelper::getParams('com_finder');

			if ($config->get('language_default', '') == '')
			{
				$defaultLang = '*';
			}
			elseif ($config->get('language_default', '') == '-1')
			{
				$defaultLang = self::getDefaultLanguage();
			}
			else
			{
				$defaultLang = $config->get('language_default');
			}

			/*
			 * The default language always has the language code '*'.
			 * In order to not overwrite the language code of the language
			 * object that we are using, we are cloning it here.
			 */
			$obj = FinderIndexerLanguage::getInstance($defaultLang);
			$defaultLanguage = clone $obj;
			$defaultLanguage->language = '*';
		}

		if (!$multilingual || $lang == '*')
		{
			$language = $defaultLanguage;
		}
		else
		{
			$language = FinderIndexerLanguage::getInstance($lang);
		}

		if (!isset($cache[$lang]))
		{
			$cache[$lang] = [];
		}

		$tokens = array();
		$terms = $language->tokenise($input);
		$terms = array_values(array_filter($terms));

		/*
		 * If we have to handle the input as a phrase, that means we don't
		 * tokenize the individual terms and we do not create the two and three
		 * term combinations. The phrase must contain more than one word!
		 */
		if ($phrase === true && count($terms) > 1)
		{
			// Create tokens from the phrase.
			$tokens[] = new FinderIndexerToken($terms, $language->language, $language->spacer);
		}
		else
		{
			// Create tokens from the terms.
			for ($i = 0, $n = count($terms); $i < $n; $i++)
			{
				if (isset($cache[$lang][$terms[$i]]))
				{
					$tokens[] = $cache[$lang][$terms[$i]];
				}
				else
				{
					$token = new FinderIndexerToken($terms[$i], $language->language);
					$tokens[] = $token;
					$cache[$lang][$terms[$i]] = $token;
				}
			}

			// Create multi-word phrase tokens from the individual words.
			if ($tuplecount > 1)
			{
				for ($i = 0, $n = count($tokens); $i < $n; $i++)
				{
					$temp = array($tokens[$i]->term);

					// Create tokens for 2 to $tuplecount length phrases
					for ($j = 1; $j < $tuplecount; $j++)
					{
						if ($i + $j >= $n || !isset($tokens[$i + $j]))
						{
							break;
						}

						$temp[] = $tokens[$i + $j]->term;
						$key = implode('::', $temp);

						// Add the token to the stack.
						if (isset($cache[$lang][$key]))
						{
							$tokens[] = $cache[$lang][$key];
						}
						else
						{
							$token = new FinderIndexerToken($temp, $language->language, $language->spacer);
							$token->derived = true;
							$tokens[] = $token;
							$cache[$lang][$key] = $token;
						}
					}
				}
			}
		}

		// Prevent the cache to fill up the memory
		while (count($cache[$lang]) > 1024)
		{
			/**
			 * We want to cache the most common words/tokens. At the same time
			 * we don't want to cache too much. The most common words will also
			 * be early in the text, so we are dropping all terms/tokens which
			 * have been cached later.
			 */
			array_pop($cache[$lang]);
		}

		return $tokens;
	}

	/**
	 * Method to get the base word of a token.
	 *
	 * @param   string  $token  The token to stem.
	 * @param   string  $lang   The language of the token.
	 *
	 * @return  string  The root token.
	 *
	 * @since   2.5
	 */
	public static function stem($token, $lang)
	{
		static $multilingual;
		static $defaultStemmer;

		if (is_null($multilingual))
		{
			$multilingual = Multilanguage::isEnabled();
			$config = ComponentHelper::getParams('com_finder');

			if ($config->get('language_default', '') == '')
			{
				$defaultStemmer = FinderIndexerLanguage::getInstance('*');
			}
			elseif ($config->get('language_default', '') == '-1')
			{
				$defaultStemmer = FinderIndexerLanguage::getInstance(self::getDefaultLanguage());
			}
			else
			{
				$defaultStemmer = FinderIndexerLanguage::getInstance($config->get('language_default'));
			}
		}

		if (!$multilingual || $lang == '*')
		{
			$language = $defaultStemmer;
		}
		else
		{
			$language = FinderIndexerLanguage::getInstance($lang);
		}

		if (isset(self::$stemmer_cache[$language->language]) && isset(self::$stemmer_cache[$language->language][$token]))
		{
			return self::$stemmer_cache[$language->language][$token];
		}

		if (!isset(self::$stemmer_cache[$language->language]))
		{
			self::$stemmer_cache[$language->language] = array();
		}

		self::$stemmer_cache[$language->language][$token] = $language->stem($token);

		return self::$stemmer_cache[$language->language][$token];
	}

	/**
	 * Method to add a content type to the database.
	 *
	 * @param   string  $title  The type of content. For example: PDF
	 * @param   string  $mime   The mime type of the content. For example: PDF [optional]
	 *
	 * @return  integer  The id of the content type.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function addContentType($title, $mime = null)
	{
		static $types;

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Check if the types are loaded.
		if (empty($types))
		{
			// Build the query to get the types.
			$query->select('*')
				->from($db->quoteName('#__finder_types'));

			// Get the types.
			$db->setQuery($query);
			$types = $db->loadObjectList('title');
		}

		// Check if the type already exists.
		if (isset($types[$title]))
		{
			return (int) $types[$title]->id;
		}

		// Add the type.
		$query->clear()
			->insert($db->quoteName('#__finder_types'))
			->columns(array($db->quoteName('title'), $db->quoteName('mime')))
			->values($db->quote($title) . ', ' . $db->quote($mime));
		$db->setQuery($query);
		$db->execute();

		// Return the new id.
		return (int) $db->insertid();
	}

	/**
	 * Method to check if a token is common in a language.
	 *
	 * @param   string  $token  The token to test.
	 * @param   string  $lang   The language to reference.
	 *
	 * @return  boolean  True if common, false otherwise.
	 *
	 * @since   2.5
	 */
	public static function isCommon($token, $lang)
	{
		static $data;
		static $default;

		$langCode = $lang;

		// If language requested is wildcard, use the default language.
		if ($lang == '*')
		{
			$default = $default === null ? substr(self::getDefaultLanguage(), 0, 2) : $default;
			$langCode = $default;
		}

		// Load the common tokens for the language if necessary.
		if (!isset($data[$langCode]))
		{
			$data[$langCode] = self::getCommonWords($langCode);
		}

		// Check if the token is in the common array.
		return in_array($token, $data[$langCode], true);
	}

	/**
	 * Method to get an array of common terms for a language.
	 *
	 * @param   string  $lang  The language to use.
	 *
	 * @return  array  Array of common terms.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function getCommonWords($lang)
	{
		$db = JFactory::getDbo();

		// Create the query to load all the common terms for the language.
		$query = $db->getQuery(true)
			->select($db->quoteName('term'))
			->from($db->quoteName('#__finder_terms_common'))
			->where($db->quoteName('language') . ' = ' . $db->quote($lang));

		// Load all of the common terms for the language.
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Method to get the default language for the site.
	 *
	 * @return  string  The default language string.
	 *
	 * @since   2.5
	 */
	public static function getDefaultLanguage()
	{
		static $lang;

		// We need to go to com_languages to get the site default language, it's the best we can guess.
		if (empty($lang))
		{
			$lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		}

		return $lang;
	}

	/**
	 * Method to parse a language/locale key and return a simple language string.
	 *
	 * @param   string  $lang  The language/locale key. For example: en-GB
	 *
	 * @return  string  The simple language string. For example: en
	 *
	 * @since   2.5
	 */
	public static function getPrimaryLanguage($lang)
	{
		static $data;

		// Only parse the identifier if necessary.
		if (!isset($data[$lang]))
		{
			if (is_callable(array('Locale', 'getPrimaryLanguage')))
			{
				// Get the language key using the Locale package.
				$data[$lang] = Locale::getPrimaryLanguage($lang);
			}
			else
			{
				// Get the language key using string position.
				$data[$lang] = StringHelper::substr($lang, 0, StringHelper::strpos($lang, '-'));
			}
		}

		return $data[$lang];
	}

	/**
	 * Method to get the path (SEF route) for a content item.
	 *
	 * @param   string  $url  The non-SEF route to the content item.
	 *
	 * @return  string  The path for the content item.
	 *
	 * @since   2.5
	 */
	public static function getContentPath($url)
	{
		static $router;

		// Only get the router once.
		if (!($router instanceof JRouter))
		{
			// Get and configure the site router.
			$config = JFactory::getConfig();
			$router = JRouter::getInstance('site');
			$router->setMode($config->get('sef', 1));
		}

		// Build the relative route.
		$uri = $router->build($url);
		$route = $uri->toString(array('path', 'query', 'fragment'));
		$route = str_replace(JUri::base(true) . '/', '', $route);

		return $route;
	}

	/**
	 * Method to get extra data for a content before being indexed. This is how
	 * we add Comments, Tags, Labels, etc. that should be available to Finder.
	 *
	 * @param   FinderIndexerResult  &$item  The item to index as a FinderIndexerResult object.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function getContentExtras(FinderIndexerResult &$item)
	{
		// Load the finder plugin group.
		JPluginHelper::importPlugin('finder');

		JFactory::getApplication()->triggerEvent('onPrepareFinderContent', array(&$item));

		return true;
	}

	/**
	 * Method to process content text using the onContentPrepare event trigger.
	 *
	 * @param   string               $text    The content to process.
	 * @param   Registry             $params  The parameters object. [optional]
	 * @param   FinderIndexerResult  $item    The item which get prepared. [optional]
	 *
	 * @return  string  The processed content.
	 *
	 * @since   2.5
	 */
	public static function prepareContent($text, $params = null, FinderIndexerResult $item = null)
	{
		static $loaded;

		// Load the content plugins if necessary.
		if (empty($loaded))
		{
			JPluginHelper::importPlugin('content');
			$loaded = true;
		}

		// Instantiate the parameter object if necessary.
		if (!($params instanceof Registry))
		{
			$registry = new Registry($params);
			$params = $registry;
		}

		// Create a mock content object.
		$content = JTable::getInstance('Content');
		$content->text = $text;

		if ($item)
		{
			$content->bind((array) $item);
			$content->bind($item->getElements());
		}

		if ($item && !empty($item->context))
		{
			$content->context = $item->context;
		}

		if (isset($item->category_alias))
		{
			$content->category_alias = $item->category_alias;
		}

		// Fire the onContentPrepare event.
		JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_finder.indexer', &$content, &$params, 0));

		return $content->text;
	}
}
