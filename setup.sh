#!/usr/bin/env bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
# php wp-cli.phar --info
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
# wp --info
# sudo rm /usr/local/bin/wp/wp-cli.phar

# installing WP. Important to set URL same for acceptance tests
./wp db create
./wp core install --url="http://127.0.0.1:4000" --title="UnTestEd" --admin_password="admin" --admin_email="dev.dummy@1000grad.de"

# activating test plugin
./wp plugin install 1000grad-epaper
./wp plugin activate 1000grad-epaper

# preparing data for test
# ./wp term create "Game of Drones" category
# ./wp post create --post_type=page --post_status=publish --post_title='Submit a Post' --post_content="[user-submitted-posts]"

# updating plugin options: enabling "Game of Drones" category, disabling captcha
# ./wp option set usp_options '{"default_options":0,"author":1,"categories":["1","2"],"number-approved":-1,"redirect-url":"","error-message":"There was an error. Please ensure that you have added a title, some content, and that you have uploaded only images.","min-images":0,"max-images":1,"min-image-height":0,"min-image-width":0,"max-image-height":1500,"max-image-width":1500,"usp_name":"show","usp_url":"show","usp_title":"show","usp_tags":"show","usp_category":"show","usp_images":"hide","upload-message":"Please select your image(s) to upload.","usp_form_width":"300","usp_question":"1 + 1 =","usp_response":"2","usp_casing":0,"usp_captcha":"hide","usp_content":"show","success-message":"Success! Thank you for your submission.","usp_form_version":"current","usp_email_alerts":1,"usp_email_address":"davert.php@mailican.com","usp_use_author":0,"usp_use_url":0,"usp_use_cat":0,"usp_use_cat_id":"","usp_include_js":1,"usp_display_url":"","usp_form_content":"","usp_richtext_editor":0,"usp_featured_images":0}' --format=json
