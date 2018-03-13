<?php
/* 
 **************************************************************************
 *
 * OpenSRS-PHP
 *
 * Copyright (C) 2000-2004 Colin Viebrock
 *
 * Version 2.8.0
 *   15-Dec-2004
 *
 **************************************************************************
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 **************************************************************************
 *
 * vim: set expandtab tabstop=4 shiftwidth=4:
 * $Id: openSRS_base.php,v 1.16.2.4 2004/12/07 20:27:22 cviebrock Exp $
 *
 **************************************************************************
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

require_once(dirname(__FILE__).'/../opensrs/openSRS_base.php');

/**
 * Class resellone_base
 *
 * An extender for the openSRS base to define the differences between ResellOne and OpenSRS.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
class resellone_base extends openSRS_base {

    /**
     * Define variables to override openSRS_base
     */
    var $LIVE_host              = 'resellers.resellone.net';
    var $LIVE_port              = 52000;
    var $LIVE_sslport           = 52443;

    var $crypt_type             = 'Blowfish';           /* 'DES' or 'BLOWFISH' or 'SSL' */

    /**
     * @param string|null $environment - Which environment to use (LIVE or TEST or HRS)
     * @param string|null $protocol - Which protocol to use (XCP or TPP)
     * @param string|null $username - Username defined in Module Settings
     * @param string|null $privateKey - Private Key defined in Module Settings
     *
     * @return bool
     */
    function resellone_base($environment = null, $protocol = null, $username = null, $privateKey = null)
    {
        if ($environment == 'test') {
            /**
             * The test mode loads the OpenSRS test mode which requires a different crypt type
             * which is why we set this here.
             */
            $this->setCryptType('SSL');
        }
        return $this->openSRS_base($environment, $protocol, $username, $privateKey);
    }

    /**
     * Set the crypt type to be used in the class.
     *
     * @param string $cryptType - DES, BlowFish or SSL.
     */
    public function setCryptType($cryptType = 'Blowfish')
    {
        if (in_array(strtolower($cryptType), array('des', 'blowfish', 'ssl'))) {
            $this->crypt_type = $cryptType;
        }
    }
}
