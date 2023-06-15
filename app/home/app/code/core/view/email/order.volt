<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <title></title>

  <style type="text/css">
    .vl {
      border-left: 2px dotted darkgrey;
      height: 100px;
    }
  </style>
</head>
<body style="margin:0; padding:0; background-color:#FFFF;">
<center>
  <div style="margin-top: 2%;display: block;">
    <table width="50%" border="0" cellpadding="0" cellspacing="8" >
      <tr>
        <td align="left" colspan="4">
          <img style="display: block;" src="{{ banner }}" width="600" border="0" alt="Banner Image" >
        </td>
      </tr>
    </table>
  </div>
  <div style="margin-top: 2%;display: block;">
    <table width="50%" border="0" cellpadding="0" cellspacing="8" >
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 150%;font-weight:bold;font-family: Times New Roman" colspan="4">
          Hi {{ username }}
        </td>
      </tr>
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 110%;font-family: Times New Roman" colspan="4">
          Congratulations !!! You have received a new Order.Here is the summary of your order:
        </td>
      </tr>
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 110%;font-family: Times New Roman;font-weight: bold;" colspan="2">
          {{ marketplace }} Order Id
        </td>
        <td align="left" style="padding-top: 1%;font-size: 110%;font-family: Times New Roman" colspan="2">
          {{ marketplace_order_id }}
        </td>
      </tr>
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 110%;font-family: Times New Roman;font-weight: bold;" colspan="2">
          Shopify Order Id
        </td>
        <td align="left" style="padding-top: 1%;font-size: 110%;font-family: Times New Roman" colspan="2">
          {{ shopify_order_id }}
        </td>
      </tr>
    </table>
  </div>
  <div style="margin-top: 2%;display: block;">
    <table width="50%" border="0" style="border: 1px solid grey;box-shadow: 2px;" cellpadding="0" cellspacing="8" >
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 150%;color:grey;font-family: Times New Roman" colspan="4" valign="top">
          Order Details
        </td>
      </tr>
      <tr style="font-size: 100%;font-weight: bold">
        <td width="55%">Item</td>
        <td width="15%">Quantity</td>
        <td width="15%">Price</td>
        <td width="15%">Total</td>
      </tr>
      <tr style="font-size: 110%">
        <td colspan="4"><hr/></td>
      </tr>
      <tr style="font-size: 110%">
        <td width="55%"></td>
        <td width="15%"></td>
        <td width="15%"></td>
        <td width="15%"></td>
      </tr>
      {% for item in line_items  %}
        <tr style="font-size: 95%;margin-top: 10px">
          <td width="55%">{{ item['title'] }}</td>
          <td width="15%" style="text-align: center;">{{ item['quantity'] }}</td>
          <td width="15%">{{ item['unit_price'] }}</td>
          <td width="15%">{{ item['total_price'] }}</td>
        </tr>
        {% endfor %}
      <tr style="min-height: 5%">
        <td width="55%"></td>
        <td width="15%"></td>
        <td width="15%"></td>
        <td width="15%"></td>
      </tr>

      <tr>
        <td colspan="4"><hr style="margin-top: 5%;border-top: dotted 1px;"/></td>
      </tr>
      <tr>
        <td width="55%"></td>
        <td width="15%"></td>
        <td width="15%"><p style="margin-top: 2%">Order Total</p></td>
        <td width="15%"><p style="margin-top: 2%">{{ total_price }}</p></td>
      </tr>
    </table>
  </div>
  <div style="margin-top: 2%; display: block;">
    <table width="50%" border="0" style="border: 1px solid grey;box-shadow: 2px;" cellpadding="0" cellspacing="8" >
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 150%;color:grey;font-family: Times New Roman" colspan="4" valign="top">
          Billing Information
        </td>
      </tr>
      <tr style="font-size: 110%">
        <td width="100%"><div style="display: block;font-weight: bold;">
          Billing Address
        </div>
          <div style="display: block;">
            {{ billing_address[0] }}
          </div>
          <div style="display: block;">
            {{ billing_address[1] }}
          </div>
          <div style="display: block;">
            {{ billing_address[2] }}
          </div>
          <div style="display: block;">
            {{ billing_address[3] }}
          </div>
        </td>
      </tr>

    </table>
  </div>
  <div style="margin-top: 2%; display: block;">
    <table width="50%" border="0" style="border: 1px solid grey;box-shadow: 2px;" cellpadding="0" cellspacing="8" >
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 150%;color:grey;font-family: Times New Roman" colspan="4" valign="top">
          Customer Information
        </td>
      </tr>
      <tr style="font-size: 110%">
        <td width="100%">
          <div style="display: block;font-weight: bold;">
            Name
          </div>
        </td>
    </tr>
    <tr style="font-size: 110%">
        <td width="100%">
          <div style="display: block;">
            {{ customer['name'] }}
          </div>
        </td>
    </tr>
    <tr style="font-size: 110%">
        <td width="100%">
          <div style="display: block;font-weight: bold;">
            Email
          </div>
        </td>
    </tr>
    <tr style="font-size: 110%">
        <td width="100%">
          <div style="display: block;">
            {{ customer['email'] }}
          </div>
        </td>
      </tr>

    </table>
  </div>
  <div style="margin-top: 2%; display: block;">
    <table width="50%" border="0" style="border: 1px solid grey;box-shadow: 2px;background-color: azure" cellpadding="0" cellspacing="8" >
      <tr>
        <td align="left" style="padding-top: 1%;font-size: 150%;color:grey;font-family: Times New Roman" colspan="4" valign="top">
          Suggestions :
        </td>
      </tr>
      <tr style="font-size: 110%">
        <td width="100%"><div style="display: block;font-size: 15px">
          <ol style="padding-left: 5%">
            <li style="padding: 1%"> Use Orders Section in Google Express Integration App to view the details of received orders.</li>
            <li style="padding: 1%"> Orders are fetched from Google Express marketplace and created on your Shopify store in aprroximately 30 minutes duration.</li>
            <li style="padding: 1%">
              Google recommends to fulfill orders after 30 minutes of order creation on
               merchant center.
            </li>
          </ul>
        </div>
        </td>
      </tr>

    </table>
  </div>
  <div style="margin-top: 2%; display: block;">
    <table border="0" cellpadding="0" cellspacing="0" width="50%" style="margin: 0; padding: 0; margin-top: 2%">
      <tr>
        <td align="center" valign="top">
          <!--[if (gte mso 9)|(IE)]>
          <table align="center" border="0" cellspacing="0" cellpadding="0" width="500">
            <tr>
              <td align="center" valign="top" width="500">
          <![endif]-->
          <table border="0" cellpadding="0" cellspacing="0" width="100%" class="responsive-table">
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
                                    <span style="display: block;color: #fff;font-size: 14px;line-height: 24px;">© 2018 Cedcommerce  All rights reserved.</span>
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
  </div>
</div>
</center>
</body>

        </html>