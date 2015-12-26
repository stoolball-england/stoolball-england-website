<?php
/**
 * Types of permission which may be granted to user roles
 *
 */
class PermissionType
{    
    /**
     * Indicator that a permission scope is global rather than limited to a particular resource
     */
    const GLOBAL_PERMISSION_SCOPE = "global";

    /**
     * Gets a description of a permission
     * @param int $type
     * @return string
     */
    public static function Text($type)
    {
        switch ($type)
        {
            case PermissionType::ViewPage(): return 'view a page';
            case PermissionType::ForumAddTopic(): return "create a forum topic";
            case PermissionType::ForumAddMessage(): return "post a forum message";
            case PermissionType::ForumSubscribe(): return "subscribe to forum alerts";
            case PermissionType::MANAGE_FORUMS: return "manage the forum";
            case PermissionType::EditPersonalInfo(): return "edit own profile";
            case PermissionType::MANAGE_CATEGORIES: return "manage categories";
            case PermissionType::MANAGE_USERS_AND_PERMISSIONS: return "manage users and permissions";
            case PermissionType::AddImage(): return "add and edit own images";
            case PermissionType::AddMediaGallery(): return "add and edit own albums";
            case PermissionType::MANAGE_ALBUMS: return "manage albums";
            case PermissionType::ApproveImage(): return "approve uploaded images";
            case PermissionType::PageSubscribe(): return "subscribe to alerts for comments on pages";
            case PermissionType::MANAGE_URLS: return "manage URLs";
            case PermissionType::MANAGE_SEARCH: return "manage search";
            case PermissionType::VIEW_ADMINISTRATION_PAGE: return "view the admin menu";
            case PermissionType::VIEW_WORDPRESS_LOGIN: return "view the WordPress login link";
            case PermissionType::EXCLUDE_FROM_ANALYTICS: return "opt out of Google Analytics";
            case PermissionType::MANAGE_TEAMS: return "manage teams and clubs";
            case PermissionType::MANAGE_COMPETITIONS: return "manage competitions and seasons";
            case PermissionType::MANAGE_GROUNDS: return "manage grounds";
            case PermissionType::ADD_MATCH: return 'add matches';
            case PermissionType::EDIT_MATCH: return "edit own matches, and results of any match";
            case PermissionType::DELETE_MATCH: return "delete own matches";
            case PermissionType::MANAGE_MATCHES: return "manage matches";
            case PermissionType::MANAGE_PLAYERS: return "manage players";
            case PermissionType::MANAGE_STATISTICS: return "manage match statistics";
        }
    }
        
	/**
	* @return int
	* @desc Permission to view an ordinary page
	*/
	public static function ViewPage() { return 1; }
	public static function ForumAddTopic() { return 2; }
	public static function ForumAddMessage() { return 3; }
	public static function ForumSubscribe() { return 4; }
    
    /**
     * Permission to manage topics and messages in the forums 
     */
    const MANAGE_FORUMS = 16;
    
	/**
	* @return int
	* @desc Permission to edit details of your account
	*/
	public static function EditPersonalInfo() { return 5; }

	/**
	* @return int
	* @desc Permission to add or edit a category
	*/
	const MANAGE_CATEGORIES = 7;
	
	/**
     * Permission to add, edit and delete users, roles and permissions
     */
	const MANAGE_USERS_AND_PERMISSIONS = 8;

	/**
	* @return int
	* @desc Permission to add or edit Images
	*/
	public static function AddImage() { return 9; }

	/**
	* @return int
	* @desc Permission to add or edit media galleries
	*/
	public static function AddMediaGallery() { return 10; }

    /**
     * Permission to edit albums and photos added by others
     */
    const MANAGE_ALBUMS = 11;
    
	/**
	* @return int
	* @desc Permission to subscribe to comments on a page of content
	*/
	public static function PageSubscribe() { return 12; }

	/**
	 * Permission to approve or reject uploaded images
	 *
	 * @return int
	 */
	public static function ApproveImage() { return 13; }
    
    
    /**
     * Permission to manage URLs, including regenerating the derived list
     */
    const MANAGE_URLS = 14;
    
    /**
     * Permission to manage the search engine
     */
    const MANAGE_SEARCH = 15;

    /**
     * Permission to view the admin index page
     */
    const VIEW_ADMINISTRATION_PAGE = 17;
    
    /**
     * Permission to view the WordPress login link
     */
    const VIEW_WORDPRESS_LOGIN = 18;

    /**
     * Users with this administration permission should not be tracked for web analytics
     */    
    const EXCLUDE_FROM_ANALYTICS = 19;
    
	/**
     * Permission to add, edit and delete teams and clubs
     */
	const MANAGE_TEAMS = 3000;
	
	/**
     * Permission to edit competitions and seasons
     */
	const MANAGE_COMPETITIONS = 3001;
	
	/**
     * Permission to add, edit and delete grounds
     */
	const MANAGE_GROUNDS = 3003;

    /**
     * Permission to add any match
     */
    const ADD_MATCH = 3002; 
    
    /**
     * Permission to edit any match. Sometimes combined with a check on the resource.
     */
	const EDIT_MATCH = 3004;
    
    /**
     * Permission to delete any match. Always combined with a check on the resource.
     */
    const DELETE_MATCH = 3006;
    
    /**
     * Permission to edit any match including additional fields
     */
    const MANAGE_MATCHES = 3007;
	
	/**
     * Permission to add or edit players
     */
	const MANAGE_PLAYERS = 3005;
    
    /**
     * Permission to carry out admin functions related to match statistics
     */
    const MANAGE_STATISTICS = 3008;
}
?>