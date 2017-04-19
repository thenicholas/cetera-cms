<?php
/**
 * Cetera CMS 3 
 *
 * @package CeteraCMS
 * @version $Id$
 * @copyright 2000-2010 Cetera labs (http://www.cetera.ru) 
 * @author Roman Romanov <nicodim@mail.ru> 
 **/
 
namespace Cetera\Exception;  
 
/**
 * Ошибка авторизации
 * 
 * @package CeteraCMS
 */ 
class Auth extends CMS
{	
    /**
     * Конструктор
     *          
     * @return void             
     */    
    public function __construct()
    {        
        parent::__construct(CMS::AUTHORIZATION_FAILED, '/'.CMS_DIR.'/');
    }
}