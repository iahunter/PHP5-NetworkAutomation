<?php

/**
 * include/information/*.class.php
 *
 * Extension leveraging the information repository
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 * @package   none
 * @author    John Lavoie
 * @copyright 2009-2014 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

class Equipment_TerminalServer  extends Information
{
    public $data;
    public $category = "Equipment";
    public $type = "Equipment_TerminalServer";
    public $customfunction = "";

	public function customdata()    // This function is ONLY required if you are using stringfields!
	{
		$CHANGED = 0;
		$CHANGED += $this->customfield("name"			,"stringfield0");
		$CHANGED += $this->customfield("ip4"			,"stringfield1");
		$CHANGED += $this->customfield("location"		,"stringfield2");
		$CHANGED += $this->customfield("serialnumber"	,"stringfield3");
		$CHANGED += $this->customfield("connection"		,"stringfield4");
		$CHANGED += $this->customfield("iccid"			,"stringfield5");
		$CHANGED += $this->customfield("wirelessid"		,"stringfield6");
		if($CHANGED && isset($this->data['id'])) { $this->update(); }   // If any of the fields have changed, run the update function.
	}

	public function update_bind()   // Used to override custom datatypes in children
	{
		global $DB;
		$DB->bind("STRINGFIELD0"	,$this->data['name'			]);
		$DB->bind("STRINGFIELD1"	,$this->data['ip4'			]);
		$DB->bind("STRINGFIELD2"	,$this->data['location'		]);
		$DB->bind("STRINGFIELD3"	,$this->data['serialnumber'	]);
		$DB->bind("STRINGFIELD4"	,$this->data['connection'	]);
		$DB->bind("STRINGFIELD5"	,$this->data['iccid'		]);
		$DB->bind("STRINGFIELD6"	,$this->data['wirelessid'	]);
	}

	public function validate($NEWDATA)
	{
		if ($NEWDATA['name']        == "" ) { $this->data['error'] .= "ERROR: Invalid Name!\n";         return 0; }
		if ($NEWDATA['ip4']         == "" ) { $this->data['error'] .= "ERROR: Invalid IP Address!\n";   return 0; }
		if ($NEWDATA['location']    == "" ) { $this->data['error'] .= "ERROR: Invalid Location!\n";     return 0; }
		if ($NEWDATA['serialnumber']== "" ) { $this->data['error'] .= "ERROR: Invalid Serial!\n";       return 0; }
		return 1;
	}

	public function html_width()
	{
		$this->html_width = array();    $i = 1;
		$this->html_width[$i++] = 35;   // ID
		$this->html_width[$i++] = 200;  // Name
		$this->html_width[$i++] = 120;  // IP
		$this->html_width[$i++] = 250;  // Location
		$this->html_width[$i++] = 150;  // Serial Number
		$this->html_width[$i++] = 150;  // IMEI Number
		$this->html_width[$i++] = 150;  // SIM ICCID
		$this->html_width[0]    = array_sum($this->html_width);
	}

    public function html_list_header()
    {
        $OUTPUT = "";
        $this->html_width();
        $WIDTH = $this->html_width;

        // Information table itself
        $rowclass = "row1"; $i = 1;
        $OUTPUT .= <<<END

        <table class="report" width="{$WIDTH[0]}">
            <caption class="report">Terminal Server List</caption>
            <thead>
                <tr>
                    <th class="report" width="{$WIDTH[$i++]}">ID</th>
                    <th class="report" width="{$WIDTH[$i++]}">Name</th>
                    <th class="report" width="{$WIDTH[$i++]}">IP Address</th>
                    <th class="report" width="{$WIDTH[$i++]}">Location</th>
                    <th class="report" width="{$WIDTH[$i++]}">Serial Number</th>
                    <th class="report" width="{$WIDTH[$i++]}">IMEI Number</th>
                    <th class="report" width="{$WIDTH[$i++]}">SIM ICCID</th>
                </tr>
            </thead>
            <tbody class="report">
END;
        return $OUTPUT;
    }

    public function html_list_row($i = 1)
    {
        $OUTPUT = "";

        $rowclass = "row".(($i % 2)+1);

        $this->html_width();
        $WIDTH = $this->html_width;

        $columns = count($WIDTH)-1; $i = 1;
        $OUTPUT .= <<<END

                <tr class="{$rowclass}">
                    <td class="report" width="{$WIDTH[$i++]}">{$this->data['id']}</td>
                    <td class="report" width="{$WIDTH[$i++]}"><a href="/information/information-view.php?id={$this->data['id']}">{$this->data['name']}</a></td>
                    <td class="report" width="{$WIDTH[$i++]}">{$this->data['ip4']}</td>
                    <td class="report" width="{$WIDTH[$i++]}">{$this->data['location']}</td>
                    <td class="report" width="{$WIDTH[$i++]}">{$this->data['serialnumber']}</td>
                    <td class="report" width="{$WIDTH[$i++]}">{$this->data['wirelessid']}</td>
                    <td class="report" width="{$WIDTH[$i++]}">{$this->data['iccid']}</td>
                </tr>
END;
        return $OUTPUT;
    }

    public function html_detail()
    {
        $OUTPUT = "";

        $this->html_width();
        $WIDTH = $this->html_width;

        // Pre-information table links to edit or perform some action
        $OUTPUT .= <<<END
        <table width="{$WIDTH[0]}" border="0" cellspacing="0" cellpadding="1">
            <tr>
                <td>
END;
        if ($this->customfunction)
        {
            $OUTPUT .= <<<END

                    <ul class="object-tools" style="float: left; align: left;">
                        <li>
                            <a href="/information/information-action.php?id={$this->data['id']}&action={$this->customfunction}">{$this->customfunction}</a>
                        </li>
                    </ul>
END;
        }
        $OUTPUT .= <<<END
                </td>
                <td align="right">
                    <ul class="object-tools">
                        <li>
                            <a href="/information/information-edit.php?id={$this->data['id']}" class="viewsitelink">Edit Information</a>
                        </li>
                    </ul>
                </td>
            </tr>
        </table>
END;

        // Information table itself
        $columns = count($WIDTH)-1;
        $i = 1;
        $OUTPUT .= <<<END

        <table class="report" width="{$WIDTH[0]}">
            <caption class="report">Terminal Server Details</caption>
            <thead>
                <tr>
                    <th class="report" width="{$WIDTH[$i++]}">ID</th>
                    <th class="report" width="{$WIDTH[$i++]}">Name</th>
                    <th class="report" width="{$WIDTH[$i++]}">IP Address</th>
                    <th class="report" width="{$WIDTH[$i++]}">Location</th>
                    <th class="report" width="{$WIDTH[$i++]}">Serial Number</th>
                    <th class="report" width="{$WIDTH[$i++]}">IMEI Number</th>
                    <th class="report" width="{$WIDTH[$i++]}">SIM ICCID</th>
                </tr>
            </thead>
            <tbody class="report">
END;
        $OUTPUT .= $this->html_list_row($i++);

        $datadump = \metaclassing\Utility::dumperToString($this->data);

        $rowclass = "row".(($i % 2)+1); $i++;
        $OUTPUT .= <<<END
                <tr class="{$rowclass}"><td colspan="{$columns}">Interface Details:<pre>{$datadump}</pre></td></tr>
END;

        $OUTPUT .= $this->html_list_footer();

        return $OUTPUT;
    }

    public function html_form()
    {
        $OUTPUT = "";
        $OUTPUT .= <<<END
            <div id="nosx_form">
            <form method="post" action="{$_SERVER['PHP_SELF']}">
            <table width="500" border="0" cellspacing="2" cellpadding="1">

                <tr><td>
                    <strong>Name:</strong>
                    <input type="text" name="name" size="50" value="{$this->data['name']}">
                </td></tr>
                <tr><td>
                    <strong>IPv4 Address:</strong>
                    <input type="text" name="ip4" size="50" value="{$this->data['ip4']}">
                </td></tr>

                <tr><td>
                    <strong>Location:</strong>
                    <input type="text" name="location" size="50" value="{$this->data['location']}">
                </td></tr>

                <tr><td>
                    <strong>Model:</strong>
                    <input type="text" name="model" size="50" value="{$this->data['model']}">
                </td></tr>

                <tr><td>
                    <strong>Serial Number:</strong>
                    <input type="text" name="serialnumber" size="50" value="{$this->data['serialnumber']}">
                </td></tr>

                <tr><td>
                    <strong>Wireless ID (EMEI, ESN, or MAC):</strong>
                    <input type="text" name="wirelessid" size="50" value="{$this->data['wirelessid']}">
                </td></tr>

                <tr><td>
                    <strong>SIM ICCID:</strong>
                    <input type="text" name="iccid" size="50" value="{$this->data['iccid']}">
                </td></tr>

                <tr><td>
                    <strong>Connection Type: </strong>
                    <select name="connection" size="1">
                    <option value="{$this->data['connection']}" selected>{$this->data['connection']}</option>
                    <option value="wired">wired</option>
                    <option value="wifi">wifi</option>
                    <option value="3g ATT">3g ATT</option>
                    <option value="3g Verizon">3g Verizon</option>
                    </select>
                </td></tr>
END;

//////////////////////////////////////////////////////END OF FIELDS//////////////////////////////////////////////////////////////
        if($this->data['id'])
        {
            $OUTPUT .= <<<END
                <tr><td>
                    <input type="hidden" name="id"      value="{$this->data['id']}">
                        <input type="submit"            value="Edit Information">
                </td></tr>
END;
        }else{
            $OUTPUT .= <<<END
                <tr><td>
                    <input type="hidden" name="category"    value="{$this->data['category']}">
                    <input type="hidden" name="type"    value="{$this->data['type']}">
                    <input type="hidden" name="parent"  value="{$this->data['parent']}">
                        <input type="submit"            value="Add Information">
                </td></tr>
END;
        }
        $OUTPUT .= <<<END
            </table>
            </form>
        </div>
END;

        return $OUTPUT;
    }

}
