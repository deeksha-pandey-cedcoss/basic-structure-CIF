<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <style type="text/css">
        /* CLIENT-SPECIFIC STYLES */
        body, table, td, a{-webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;} /* Prevent WebKit and Windows mobile changing default text sizes */
        table, td{mso-table-lspace: 0pt; mso-table-rspace: 0pt;} /* Remove spacing between tables in Outlook 2007 and up */
        img{-ms-interpolation-mode: bicubic;} /* Allow smoother rendering of resized image in Internet Explorer */

        /* RESET STYLES */
        img{border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none;}
        table{border-collapse: collapse !important;}
        body{height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important;}

        /* iOS BLUE LINKS */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* MOBILE STYLES */
        @media screen and (max-width: 600px) {
            td[class="text-center"] {
                text-align: center !important;
            }
            /* ALLOWS FOR FLUID TABLES */
            .wrapper {
                width: 100% !important;
                max-width: 100% !important;
            }

            /* ADJUSTS LAYOUT OF LOGO IMAGE */
            .logo img {
                margin: 0 auto !important;
            }

            /* USE THESE CLASSES TO HIDE CONTENT ON MOBILE */
            .mobile-hide {
                display: none !important;
            }

            .img-max {
                max-width: 100% !important;
                width: 100% !important;
                height: auto !important;
            }

            /* FULL-WIDTH TABLES */
            table[class=responsive-table] {
                width: 100% !important;
            }

            /* UTILITY CLASSES FOR ADJUSTING PADDING ON MOBILE */
            .padding {
                padding: 10px 5% 15px 5% !important;
            }

            .padding-meta {
                padding: 30px 5% 0px 5% !important;
                text-align: center;
            }

            .padding-copy {
                padding: 10px 5% 10px 5% !important;
                text-align: center;
            }

            .no-padding {
                padding: 0 !important;
            }

            .section-padding {
                padding: 15px 15px 15px 15px !important;
            }

            /* ADJUST BUTTONS ON MOBILE */
            .mobile-button-container {
                margin: 0 auto;
                width: 100% !important;
            }

            .mobile-button {
                padding: 12px 30px !important;
                border: 0 !important;
                font-size: 16px !important;
                display: block !important;
            }
        }

        /* ANDROID CENTER FIX */
        div[style*="margin: 16px 0;"] { margin: 0 !important; }
    </style>
    <!--[if gte mso 12]>
    <style type="text/css">
        .mso-right {
            padding-left: 20px;
        }
    </style>
    <![endif]-->
</head>
<body style="margin: 0 !important; padding: 0 !important;">

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0; padding: 0;">
    <tr>
        <td align="center" valign="top">
            <!--[if (gte mso 9)|(IE)]>
            <table align="center" border="0" cellspacing="0" cellpadding="0" width="500">
                <tr>
                    <td align="center" valign="top" width="500">
            <![endif]-->
            <table border="0" cellpadding="0" cellspacing="0" width="600" class="responsive-table">
                <tr>
                    <td align="center" valign="top">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="center">
                                    <img style="display: block;" src="{{ banner }}" width="600" border="0" alt="Banner Image" class="img-max">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            </td>
                    </tr>
                    </table>
            <![endif]-->
        </td>
    </tr>
</table>


<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0; padding: 0;">
    <tr>
        <td align="center" valign="top">
            <!--[if (gte mso 9)|(IE)]>
            <table align="center" border="0" cellspacing="0" cellpadding="0" width="500">
                <tr>
                    <td align="center" valign="top" width="500">
            <![endif]-->
            <table border="0" cellpadding="0" cellspacing="0" width="600" class="responsive-table">
                <tr>
                    <td align="center" bgcolor="#fafafa" style="padding: 15px 15px 15px 15px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td valign="top" height="20"></td>
                    </tr>
                    <tr>
                        <td align="left">
                            <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                                <span style="color: #333333;font-size: 24px;">Dear {{ username }},</span>
                            </font>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" height="10"></td>
                    </tr>
                    <td valign="top" height="20"></td>
                </tr>

                <tr>
                    <td valign="top" height="10"></td>
                </tr>
                <tr>
                    <td align="left">
                        <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                    <span style="color: #000000;font-size: 16px;line-height: 20px;">
                    Your Running activity on {{app}} app is complete, Below are it`s details</span>
                        </font>
                    </td>
                </tr>
                <tr>
                    <td valign="top" height="30"></td>
                </tr>

                <tr>
                    <td align="left">
                        <div style="Margin:0px auto;">
                            <table align="center" border="0" cellpadding="8" cellspacing="8" role="presentation" style="border: 2px solid grey;box-shadow: 2px;width:100%;">
                                <tbody>
                                <tr>
                                    <td style="direction:ltr;font-size:0px;text-align:left;vertical-align:middle;">
                                        <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;">
                                            <table width="100%" cellspacing="5" cellpadding="5" border="0">
                                                {% for index , details in task  %}
                                                <tr>
                                                    <td valign="top" align="left">
                                                        <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                                             <span style="font-size: 15px;line-height: 24px;">
                                             <span style="font-weight: bold">{{details['label']}}</span> : <span style=" display: inline-block;">{{details['value']}}</span></span>
                                                        </font>
                                                    </td>
                                                </tr>
                                                {% endfor %}
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td valign="top" height="20"></td>
                </tr>

                <tr>
                    <td align="left">
                        {% if download['present'] %}
                        <div style="Margin:0px auto;">
                            <table align="center" border="0" cellpadding="8" cellspacing="8" role="presentation" style="border: 2px solid grey;box-shadow: 2px;width:100%;">
                                <tbody>
                                <tr>
                                    <td style="direction:ltr;font-size:0px;text-align:left;vertical-align:middle;">
                                        <div class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;">
                                            <table width="100%" cellspacing="5" cellpadding="5" border="0">

                                                <tr>
                                                    <td valign="top" align="center">
                                                        <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                                             <span style="font-size: 15px;font-weight: bold;line-height: 24px;">
                                             Download Report</span>
                                                        </font>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td valign="top" align="center" >
                                                        <div style="max-width: 500px;">
                                                            <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                                             <span style="font-size: 15px;line-height: 24px;text-align: center;word-wrap: break-word;">
                                                 <a href='{{download["url"]}}' target="_blank">Click To download</a></span>
                                                            </font>
                                                        </div>
                                                    </td>
                                                </tr>

                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        {% endif %}
                    </td>
                </tr>

                <tr>
                    <td valign="top" height="20"></td>
                </tr>
                <tr>
                    <td align="left">
                        <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                    <span style="color: #000000;font-size: 16px;">
                    Best Regards,</span>
                        </font>
                    </td>
                </tr> <tr>
                <td align="left">
                    <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                    <span style="color: #000000;font-size: 16px;">
                    Team CedCommerce.
                   </span>
                    </font>
                </td>
            </tr>
            </table>
        </td>
    </tr>
</table>
<!--[if (gte mso 9)|(IE)]>
</td>
        </tr>
        </table>
<![endif]-->
</td>
</tr>
        </table>


<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0; padding: 0;">
<tr>
    <td align="center" valign="top">
        <!--[if (gte mso 9)|(IE)]>
        <table align="center" border="0" cellspacing="0" cellpadding="0" width="500">
            <tr>
                <td align="center" valign="top" width="500">
        <![endif]-->
        <table border="0" cellpadding="0" cellspacing="0" width="600" class="responsive-table">
            <tr>
                <td align="center" bgcolor="#333" valign="top" style="padding: 15px 15px 15px 15px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td align="center" valign="top">
                                <table border="0" cellpadding="0" cellpadding="0" width="350" class="responsive-table" align="left">
                                    <tr>
                                        <td align="center" valign="top">
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td valign="top" height="10"></td>
                                                </tr>
                                                <tr>
                                                    <td align="left" valign="top" class="text-center">
                                                        <font face="'Open Sans', Arial, Helvetica, sans-serif;">
                                                            <span style="display: block;color: #fff;font-size: 14px;line-height: 24px;">© 2019 Cedcommerce  All rights reserved.</span>
                                                        </font>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td valign="top" height="10"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <!--[if (gte mso 9)|(IE)]>
        </td>
                </tr>
                </table>
        <![endif]-->
    </td>
</tr>
</table>

        </body>
        </html>

