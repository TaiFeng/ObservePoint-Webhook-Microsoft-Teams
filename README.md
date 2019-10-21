# ObservePoint-Webhook-Microsoft-Teams
PHP integration of ObservePoint's Webhook into a Microsoft Teams notification. 

This tool is designed to accept a POST request from ObservePoint web audits and web journeys (entered into the Webhook URL section) and then generate a modified POST request that will create a rich notification in Microsoft Teams via its Webhook connector.

ObservePoint Webhook Documentation: https://help.observepoint.com/articles/2879096-webhooks-use-cases

# Inputs
Example Request URL: https://mysite.com/webhook.php?token=user-auth-token&redirect=https%3A%2F%2Foutlook.office.com%2Fwebhook%2Fhex-code%2FIncomingWebhook%2Fhex-code

Query string parameters:
1. token - this is an authentication token that helps prevent misuse of the PHP script. You specify this in the file.
2. redirect - this is the URL-encoded form of the webhook URL provided by the Microsoft Teams connector.

# Dependencies
Your PHP installation needs access to cURL

# Variables to set in the file
Line 2:  ObservePoint API Key
Line 11: User-specified authentication token to ensure the right access
Line 21: The Webhook URL provided by the Microsoft Teams integration (this is only needed if you don't populate the "redirect" query string parameter described in the Inputs section)

