<?php
	/*
	 * Module:	bta_uadd.php
	 * Description: This is the add user screen of the administrative interface.
	 *		If the user messes up entering something, the screen will repopulate, except
	 *		for the password fields.
	 *
	 * Author:	danomac
	 * Written:	06-June-2004
	 *
	 * Copyright (C) 2004 danomac
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	 */

	/*
	 * Session webserver farm check
	 */
	require_once ("../config.php");

	if (isset($GLOBALS["webserver_farm"]) && isset($GLOBALS["webserver_farm_session_path"])) {
		if ($GLOBALS["webserver_farm"] && strlen($GLOBALS["webserver_farm_session_path"]) > 0) {
			session_save_path($GLOBALS["webserver_farm_session_path"]);
		}
	}
	session_start();
	header("Cache-control: private");

	/*
	 * List of the external modules required
	 */
	require_once ("../funcsv2.php");
	require_once ("../version.php");
	require_once ("../BDecode.php");
	require_once ("../BEncode.php");
	require_once ("bta_funcs.php");

	/*
	 * Get the current script name.
	 */
	$scriptname = $_SERVER["PHP_SELF"];

	/*
	 * Get the client's IP address. Used for verifying access.
	 */
	$ip = str_replace("::ffff:", "", $_SERVER["REMOTE_ADDR"]);

	/*
	 * Check to make sure person is logged in, and that the session
	 * is actually theirs.
	 */
	if (!admIsLoggedIn($ip)) {
		admShowError("You can't access this page directly.",
			     "You don't appear to be logged in. Use admin/index.php to login to the administrative interface.",
			     $adm_pageerr_title);
		exit;		
	}

	/*
	 * Group admin: are they actually allowed to view this page?
	 * If not, redirect them back to main
	 */
	if (!($_SESSION["admin_perms"]["usermgmt"] || $_SESSION["admin_perms"]["root"])) {
		admShowMsg("You don't have permission to view this page.", "Redirecting to the main administration panel.",
			       $adm_page_title, true, "bta_main.php", 3);
	}

	/*
	 * Set the message to an empty string. If there is content in this string, it
	 * is displayed in the table. Also sets an "error" variable so skipping processing
	 * can be done.
	 */
	$statusMsg = "";
	$addError = false;

	/*
	 * If the button was pressed, let's attempt to add the torrent.
	 */
	if (isset($_POST["adduser"])) {
		/*
		 * Connect to the database
		 */
		if ($GLOBALS["persist"])
			$db = @mysql_pconnect($dbhost, $dbuser, $dbpass) or die("<HTML><BODY><FONT COLOR=\"red\">Couldn't connect to database. Incorrect username/password?</FONT></BODY></HTML>");
		else
			$db = @mysql_connect($dbhost, $dbuser, $dbpass) or die("<HTML><BODY><FONT COLOR=\"red\">Couldn't connect to database. Incorrect username/password?</FONT></BODY></HTML>");
		mysql_select_db($database) or die("Can't open the database.");

		/*
		 * If there were no processing errors, check the rest of the
		 * data on the form itself.
		 */
		if (!$addError) {
			/*
			 * Check all the text fields in the form. If something was set,
			 * copy it into a variable.
			 */
			if (isset($_POST["username"]))
				$username=$_POST["username"];
			else
				$username = "";
	
			if (isset($_POST["password"]))
				$password = $_POST["password"];
			else
				$password = "";

			if (isset($_POST["category"]))
				$category = $_POST["category"];
			else
				$category = "";

			if (isset($_POST["comment"]))
				$comment = $_POST["comment"];
			else
				$comment = "";

			if (isset($_POST["perm_add"]))
				if (strcmp($_POST["perm_add"], "enabled") == 0)
					$perm["add"] = 'Y';
				else
					$perm["add"] = 'N';
			else
				$perm["add"] = 'N';

			if (isset($_POST["perm_addext"]))
				if (strcmp($_POST["perm_addext"], "enabled") == 0)
					$perm["addext"] = 'Y';
				else
					$perm["addext"] = 'N';
			else
				$perm["addext"] = 'N';

			if (isset($_POST["perm_addmirror"]))
				if (strcmp($_POST["perm_addmirror"], "enabled") == 0)
					$perm["addmirror"] = 'Y';
				else
					$perm["addmirror"] = 'N';
			else
				$perm["addmirror"] = 'N';

			if (isset($_POST["perm_edit"]))
				if (strcmp($_POST["perm_edit"], "enabled") == 0)
					$perm["edit"] = 'Y';
				else
					$perm["edit"] = 'N';
			else
				$perm["edit"] = 'N';

			if (isset($_POST["perm_delete"]))
				if (strcmp($_POST["perm_delete"], "enabled") == 0)
					$perm["delete"] = 'Y';
				else
					$perm["delete"] = 'N';
			else
				$perm["delete"] = 'N';

			if (isset($_POST["perm_retire"]))
				if (strcmp($_POST["perm_retire"], "enabled") == 0)
					$perm["retire"] = 'Y';
				else
					$perm["retire"] = 'N';
			else
				$perm["retire"] = 'N';

			if (isset($_POST["perm_unhide"]))
				if (strcmp($_POST["perm_unhide"], "enabled") == 0)
					$perm["unhide"] = 'Y';
				else
					$perm["unhide"] = 'N';
			else
				$perm["unhide"] = 'N';

			if (isset($_POST["perm_peers"]))
				if (strcmp($_POST["perm_peers"], "enabled") == 0)
					$perm["peers"] = 'Y';
				else
					$perm["peers"] = 'N';
			else
				$perm["peers"] = 'N';

			if (isset($_POST["perm_viewconf"]))
				if (strcmp($_POST["perm_viewconf"], "enabled") == 0)
					$perm["viewconf"] = 'Y';
				else
					$perm["viewconf"] = 'N';
			else
				$perm["viewconf"] = 'N';

			if (isset($_POST["perm_retiredmgmt"]))
				if (strcmp($_POST["perm_retiredmgmt"], "enabled") == 0)
					$perm["retiredmgmt"] = 'Y';
				else
					$perm["retiredmgmt"] = 'N';
			else
				$perm["retiredmgmt"] = 'N';

			if (isset($_POST["perm_ipban"]))
				if (strcmp($_POST["perm_ipban"], "enabled") == 0)
					$perm["ipban"] = 'Y';
				else
					$perm["ipban"] = 'N';
			else
				$perm["ipban"] = 'N';

			if (isset($_POST["perm_usermgmt"]))
				if (strcmp($_POST["perm_usermgmt"], "enabled") == 0)
					$perm["usermgmt"] = 'Y';
				else
					$perm["usermgmt"] = 'N';
			else
				$perm["usermgmt"] = 'N';

			if (isset($_POST["perm_advsort"]))
				if (strcmp($_POST["perm_advsort"], "enabled") == 0)
					$perm["advsort"] = 'Y';
				else
					$perm["advsort"] = 'N';
			else
				$perm["advsort"] = 'N';
				
			/*
			 * Make sure the mandatory fields are filled out
			 */
			if (strlen($username) < 8) {
				$statusMsg .= "You must specify a username at least 8 characters in length!";
				$addError = true;
			} else {
				if (strpos($username, " ") !== false) {
					$statusMsg .= "Usernames cannot contain spaces!";
					$addError = true;
				}
			}

			if (strlen($password) < 8) {
				if (strlen($statusMsg) > 0) $statusMsg .= "<BR>";
				$statusMsg .= "You must specify a password at least 8 characters in length!";
				$addError = true;
			} else
				/*
				 * Make sure the password is the one intended...
				 */
				if (strcmp($password, $_POST["reenterpassword"]) != 0) {
					if (strlen($statusMsg) > 0) $statusMsg .= "<BR>";
					$statusMsg .= "Passwords do not match.";
					$addError = true;
				} else
					/*
					 * Need to hash this for the login to work...
					 */
					$password = md5($password);

			if (strlen($category)==0) {
				if (strlen($statusMsg) > 0) $statusMsg .= "<BR>";
				$statusMsg .= "You must specify a category to restrict the user to.";
				$addError = true;
			} else {
				if (strpos($category, " ") !== false) {
					if (strlen($statusMsg) > 0) $statusMsg .= "<BR>";
					$statusMsg .= "Category names cannot contain spaces!";
					$addError = true;
				}
			}

			/*
			 * If the above tests pass, THEN add the user.
			 */
			if (!$addError) {
				/*
				 * Build the query string
				 */
				$query = "INSERT INTO adminusers (username, 
								password, 
								category, 
								comment, 
								perm_add,
								perm_addext, 
								perm_mirror, 
								perm_edit, 
								perm_delete, 
								perm_retire, 
								perm_unhide, 
								perm_peers, 
								perm_viewconf, 
								perm_retiredmgmt,
								perm_ipban,
								perm_usermgmt,
								perm_advsort)
								VALUES (\"$username\", 
									\"$password\", 
									\"$category\", 
									\"$comment\", 
									\"".$perm["add"]."\", 
									\"".$perm["addext"]."\", 
									\"".$perm["addmirror"]."\", 
									\"".$perm["edit"]."\", 
									\"".$perm["delete"]."\", 
									\"".$perm["retire"]."\", 
									\"".$perm["unhide"]."\", 
									\"".$perm["peers"]."\", 
									\"".$perm["viewconf"]."\", 
									\"".$perm["retiredmgmt"]."\",
									\"".$perm["ipban"]."\",
									\"".$perm["usermgmt"]."\",
									\"".$perm["advsort"]."\")";

				/* 
				 * Add the user to the database.
				 */
				$status = mysql_query($query);
	
				/*
				 * Display a status to the user
				 */
				if (!$status)
					$statusMsg = "There were some errors. Does this user exist already?";
				else
					$statusMsg = "User added successfully.";
			}
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META NAME="Author" CONTENT="danomac">
<LINK REL="stylesheet" HREF="admin.css" TYPE="text/css" TITLE="Default">
<?php
	/*
	 * Set the page title.
	 */
	echo "<TITLE>". $adm_page_title . " - Add user</TITLE>\r\n";
?>
</HEAD>

<BODY>
<FORM ENCTYPE="multipart/form-data" METHOD="POST" ACTION="bta_uadd.php">
<TABLE CLASS="tblAdminOuter">
<TR>
<?php
	/*
	 * Display the page heading.
	 */
	echo "\t<TD CLASS=\"pgheading\" COLSPAN=15>".$adm_page_title."<BR>Add user</TD>\r\n";
?>
</TR>
<?php admShowURL_Login($ip); ?>
<TR>
	<TD CLASS="data" COLSPAN=15 ALIGN="center"><BR>
	   <A HREF="help/help_add_user.php" TARGET="_blank">Need help?</A><BR>
	</TD>
</TR>
<TR>
	<TD ALIGN="center" COLSPAN=15>
	<TABLE BORDER=1>
<?php
	/*
	 * Display the status of the operation, if there is a message.
	 */
	if (strlen($statusMsg) > 0)
		echo "\t<TR>\r\n\t\t<TD ALIGN=\"center\" COLSPAN=15><DIV CLASS=\"status\">$statusMsg</DIV></TD>\r\n\t</TR>";
?>
	<TR>
		<TD COLSPAN=3 ALIGN="center"><A HREF="bta_usermgmt.php">Return to User Administrative screen.</A><BR>&nbsp;</TD>
	</TR>
	<TR>
		<TD WIDTH="33%" ALIGN="right">Username:</TD>
		<TD><INPUT TYPE=text NAME="username" SIZE=40 MAXLENGTH=32 <?php if ($addError) echo "VALUE=\"".$_POST["username"]."\""; ?>></TD><TD>&nbsp;&nbsp; Enter the new username (8 character minimum.)</TD>
	</TR>
	<TR>
		<TD WIDTH="33%" ALIGN="right">Password:</TD>
		<TD WIDTH="33%" ALIGN="left"><INPUT TYPE=password NAME="password" SIZE=40 MAXLENGTH=200></TD><TD>&nbsp;&nbsp; Enter the password for this username (8 character minimum.)</TD>
	</TR>
	<TR>
		<TD WIDTH="33%" ALIGN="right">Re-enter Password:</TD>
		<TD WIDTH="33%" ALIGN="left"><INPUT TYPE=password NAME="reenterpassword" SIZE=40 MAXLENGTH=200></TD><TD>&nbsp;&nbsp; Re-enter the password for verification.</TD>
	</TR>
	<TR>
		<TD WIDTH="33%" ALIGN="right">Category:</TD>
		<TD WIDTH="33%" ALIGN="left"><INPUT TYPE=text NAME="category" SIZE=12 MAXLENGTH=10 <?php if ($addError) echo "VALUE=\"".$_POST["category"]."\""; ?>></TD><TD>&nbsp;&nbsp; Enter the category username is restricted to.</TD>
	</TR>
	<TR>
		<TD WIDTH="33%" ALIGN="right">Comment:</TD>
		<TD WIDTH="33%" ALIGN="left"><INPUT TYPE=text NAME="comment" SIZE=40 MAXLENGTH=200 <?php if ($addError) echo "VALUE=\"".$_POST["comment"]."\""; ?>></TD><TD>&nbsp;&nbsp;<FONT SIZE=-1><B>(Optional)</B></FONT> Enter a comment (displayed on the main page when user logs in.</TD>
	</TR>
	<TR>
		<TD ALIGN="center" COLSPAN=3><B>Permissions</B><SUP>1</SUP></TD>
	</TR>
	<TR>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_add" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_add"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> Add torrents
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_addext" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_addext"], "enabled")==0) echo "CHECKED"; } ?>> Add external torrents<SUP>2</SUP></TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_addmirror" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_addmirror"], "enabled")==0) echo "CHECKED"; } ?>> Add mirror torrents<SUP>3</SUP></TD>
	</TR>
	<TR>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_edit" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_edit"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> Edit torrents</TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_delete" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_delete"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> Delete torrents</TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_retire" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_retire"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> Retire torrents<SUP>4</SUP></TD>
	</TR>
	<TR>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_unhide" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_unhide"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> Unhide torrents<SUP>5</SUP></TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_peers" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_peers"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> View peers<SUP>6</SUP></TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_viewconf" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_viewconf"], "enabled")==0) echo "CHECKED"; } ?>> View tracker configuration</TD>
	</TR>
	<TR>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_retiredmgmt" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_retiredmgmt"], "enabled")==0) echo "CHECKED"; } else echo "CHECKED"; ?>> Retired torrent management<SUP>7</SUP></TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_ipban" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_ipbans"], "enabled")==0) echo "CHECKED"; } ?>> Allow IP Banning<SUP>8</SUP></TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_usermgmt" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_usermgmt"], "enabled")==0) echo "CHECKED"; } ?>> Manage users</TD>
	</TR>
	<TR>
		<TD ALIGN=CENTER>&nbsp;</TD>
		<TD ALIGN=CENTER><INPUT TYPE="checkbox" NAME="perm_advsort" VALUE="enabled" <?php if ($addError) { if (strcmp($_POST["perm_advsort"], "enabled")==0) echo "CHECKED"; } ?>> Allow Advanced Sorting<SUP>9</SUP></TD>
		<TD ALIGN=CENTER>&nbsp;</TD>
	</TR>
	<TR>
		<TD ALIGN=CENTER COLSPAN=3><INPUT TYPE=submit NAME="adduser" VALUE="Add User" CLASS="button">&nbsp;&nbsp;<INPUT TYPE=reset VALUE="Clear settings" CLASS="button"></TD>
	</TR>
	<TR>
		<TD COLSPAN=3><BR><B>NOTES/EXPLANATION OF PERMISSIONS:</B><ol><li>These permissions apply ONLY to the category specified in the category field. The user will NOT have these permissions to other categories at all.</li><li>External torrents refer to those with an announce URL other that this tracker. External scanning can be enabled; see manual.</li><li>Allows this tracker to be a backup of another tracker, and requires the announce url to be in the torrent as a backup.</li><li>This applies for the main administration page. If you allow retired torrent management, they still are not allowed to retire torrents.</li><li>Applies to the main administration page. If you allow editing of torrents user will be able to unhide the torrent manually.</li><li>Includes deleting of peers from torrents in the specified category. DOES NOT include banning of peers unless user is allowed to use the IP Banning interface.</li><li>This is for the retired torrent management screen; it does not include permissions to actually retire a torrent.</li><li>IP Banning is TRACKER WIDE. Be careful about who you give this permission to.</li><li>Allows users to group and manually sort their own torrents.</li></ol></TD>
	</TR>
	<TR>
		<TD COLSPAN=3 ALIGN="center"><BR><A HREF="bta_usermgmt.php">Return to User Administrative screen.</A></TD>
	</TR>
	</TABLE>
	</TD>
</TR>
</TABLE> 
</FORM>
</BODY>
</HTML>