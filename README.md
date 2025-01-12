# Microsoft-authentication-token-use in a Dynamics Contact Form WordPress Plugin

This plugin integrates **Contact Form 7** (CF7) with Microsoft Dynamics CRM API. It allows form submissions on your WordPress site to be pushed directly to your Dynamics CRM as a lead, based on specific user inputs.

## Features

- Submits Contact Form 7 data directly to Microsoft Dynamics CRM.
- Automatic authentication and token refresh flow using OAuth 2.0.
- Pushes lead data to CRM with specific fields like name, company, email, telephone, country, and other custom data.

## Installation

1. **Install Contact Form 7 plugin** if you don't have it already installed.
   
2. **Upload the Plugin Files**:
   - Place the Dynamics Contact Form plugin files into your WordPress plugins directory (`wp-content/plugins/`).

3. **Activate the Plugin**: 
   - Go to the WordPress admin panel, navigate to `Plugins`, and activate the **Dynamics Contact Form** plugin.

4. **Configure API Settings**:
   - Ensure that you have registered your application in Microsoft Azure for OAuth2.0 authentication.
   - Set the `client_id`, `client_secret`, and `resource` variables in the plugin file (`dynamicsContactForm.php`).

## Authentication Flow

The plugin uses OAuth 2.0 to authenticate and refresh tokens required for connecting to Microsoft Dynamics API. Here's how the flow works:

### 1. **Initial Authentication**

To authenticate for the first time:
   - Run the following command in your terminal:
   ```bash
   wp dynamics:auth
   ```
   This will output an authorization URL in the terminal.

   - Open the provided URL in your browser and log in with the necessary credentials (`crmintegrations@enva.com` with password `Welcome123`).

   - After successful login, you'll be redirected to a page with an authorization code.
   
   - Copy this authorization code and paste it into the terminal when prompted:
   ```bash
   Enter verification code:
   ```

   - Once the verification code is entered, the plugin will exchange it for an access token and store it in the `token.json` file for future use.

### 2. **Token Refresh**

If the access token expires:
   - The plugin checks the token expiry before sending the data to CRM.
   - If the token is expired, it will automatically refresh the token using the stored `refresh_token` in `token.json`.
   - The token file will be updated with the new access token and expiry time.

### 3. **Storing Tokens**

   - The plugin stores the authentication tokens (both access and refresh tokens) in a file named `token.json` located in the plugin directory.
   - The file contains an `access_token`, `refresh_token`, and `expireTime`.
   
### 4. **Using the Access Token**

When sending lead data to the CRM, the plugin uses the access token for authorization:
   - It retrieves the stored token from `token.json`.
   - If the token is still valid, it is used to authenticate the request.
   - If expired, the token is refreshed first.

## Contact Form Data Flow

The plugin hooks into the `wpcf7_before_send_mail` action, which is triggered before an email is sent by Contact Form 7. Here's the flow of the data:

### 1. **Form Submission**

When a user submits the form on your website, the plugin collects the following data:

- **Name** (`yourName`)
- **Surname** (`yourSurname`)
- **Company** (`yourCompany`)
- **Job Title** (`yourJobTitle`)
- **Email** (`yourEmail`)
- **Telephone** (`yourTel`)
- **Country** (`yourCountry`)
- **Postcode** (`yourPostcode`)
- **Enquiry Source** (`enquiryWhere`)
- **Message** (`yourMessage`)
- **Privacy Policy Agreement** (`accept-this-1`)

### 2. **Validating Data**

Each field is validated:
   - If a field is missing or empty, an error message will be generated.
   - If all required fields are present, the plugin constructs the `$crm_data` array with the values.

### 3. **Lead Source Mapping**

The plugin maps the `enquiryWhere` field to a CRM lead source:
- `"Search Engine"` => `281490001`
- `"Social Media"` => `289230000`
- `"Online Advert"` => `289230001`
- `"Print Advertisement"` => `1`
- `"Existing Customer"` => `281490000`
- `"Word of Mouth/Enva Employee"` => `2`
- `"Event/Conference"` => `6`
- **Default** => `10` (for any other input)

### 4. **Sending Data to CRM**

Once the data is validated, the plugin sends the `$crm_data` to the Dynamics CRM API using a `POST` request. This includes the user's details, company, job title, and lead source information.

The API request is sent using **cURL** with the following headers:
- `Authorization: Bearer <access_token>`
- `Content-Type: application/json`

The CRM lead is created via the Dynamics API endpoint:
```
POST https://trialsandbox.api.crm4.dynamics.com/api/data/v9.1/leads
```

If successful, the lead will be added to Dynamics CRM. If there’s an error, the plugin will output the error for debugging purposes.

### 5. **Error Handling**

If any issues arise with the data submission or API request:
   - The plugin will display an error message (e.g., invalid token, failed request, missing data).
   - You will be able to see the error details in the `var_dump()` output, which helps in debugging.

## CLI Commands

- **Authenticate & Refresh Token**:
  Use `wp dynamics:auth` to authenticate and refresh the token.

## Troubleshooting

- Ensure your `client_id`, `client_secret`, and `resource` are correctly configured in the plugin file.
- If the token file is corrupted or missing, rerun the `wp dynamics:auth` command to regenerate the authentication token.
- Check the cURL response for errors if the lead isn’t created in CRM.

## License

This plugin is open source and available under the GPLv2 license.

---

By following these steps, you can integrate your WordPress site’s Contact Form 7 with Microsoft Dynamics CRM and automatically push lead data into CRM. The authentication process ensures secure and seamless communication between the systems.
