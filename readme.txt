=== Plugin Name ===
Contributors: @maurohmartinez
Donate link: https://www.paypal.me/maurohmartinez
Tags: push notifications, expo, web, app, connect
Requires at least: 4.7
Tested up to: 5.4
Stable tag: 1.0.10
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://inspiredpulse.com/

This is a free plugin to send push notifications using Expo Api https://expo.io/notifications. In order to use this plugin, you need an App built with React Native and Expo. This plugin creates an API to interact with your native App and stores users unique push tokens to be able to send them notifications from your Admin Panel.

== Description ==

This is a free plugin to send push notifications using Expo Api https://expo.io/notifications. In order to use this plugin, you need an App built with React Native and Expo. This plugin creates an API to interact with your native App and stores users unique push tokens to eb able to send them notifications from your Admin Panel.

== Frequently Asked Questions ==

= How can I use it? =

In order to use this plugin, you need an App built with React Native and Expo. This plugin creates an API to interact with your native App and stores users unique push tokens to eb able to send them notifications from your Admin Panel.

= Is it free? =

Yes.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png).
2. This screen shot description corresponds to screenshot-2.(png).
3. This screen shot description corresponds to screenshot-3.(png).

== Changelog ==

= 1.0.10 =
- Spanish languages supported.
- Security improvements for Rest Api.

= 1.0.0 =
First realease.

== Characteristics ==

1. Send push notifications
2. Register screens for your App and redirect users to specific content when they open notifications.
3. Keep track of all your notifications sent and the users registered to your App.
4. Save unfinished push notifications as draft and return later to finish and send them.

== Rest API ==

Fusion Web App registers the next API routes for you to connect to your wordpress site.

Note: We strongly recommend to use htpps in order to protect your site.

**- your_site/wp-json/app/appregister-user-token**
Type: POST
Action: It registers the user's push token and stores the information in the database.
Authentication: Basic authentification by providing user credentials.
Response: [success: true] - status 201. Else [success: false, error: error_code] - status 201.
Params:
    $username: A real active user in your wordpress site. It does not need to have admin roles, a subscriber is enough.
    $password: The password for the user provided.
    $installation_id: The Expo installation ID. Use import Constants from 'expo-constants'; Constants.installationId.
    $name_device: The name of the user's device Use import Constants from 'expo-constants'; Constants.deviceName.
    $platform: only accepts 'ios' or 'android'. Use import { Platform } from 'react-native'; Platform.OS.
    $token: Expo push notification token. For more info on this read the [documentation](https://docs.expo.io/versions/latest/sdk/notifications/).

**- your_site/wp-json/app/remove-user-token**
Type: POST
Authentication: Basic authentification by providing user credentials.
Response: [device_removed: true] - status 201. Else [device_removed: false, error: error_code] - status 201.
Action: It removes the user's push token. Use this route when your users logout from your application and stop sending them push notifications. Also, you can use it in case your app allows users to choose whether to receive or not notifications in the settings.
Params:
    $username: A real active user in your wordpress site. It does not need to have admin roles, a subscriber is enough.
    $password: The password for the user provided.
    $installation_id: The Expo installation ID. Use import Constants from 'expo-constants'; Constants.installationId.
    $token: Expo push notification token. For more info on this read the [documentation](https://docs.expo.io/versions/latest/sdk/notifications/).

**- your_site/wp-json/app/get-main-categories**
Type: GET
Authentication: None
Response: Array(['blogs' => $blogs, 'audios' => $audios, 'videos' => $videos ])
Action: Use this route to fetch the main categories (those without parents) of your website.
Params: None.