=== FAQ Generator AI ===
Contributors: dadashzadeh
Donate link: https://dadashzadeh.org/donate/
Tags: faq, ai, openai, schema, seo, rank-math, yoast
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate FAQs using AI with TinyMCE integration. Compatible with RankMath.

== Description ==

**FAQ Generator AI** is a powerful WordPress plugin that uses artificial intelligence to automatically generate frequently asked questions (FAQs) for your content. Perfect for bloggers, businesses, and content creators who want to improve their SEO and provide better user experience.

= ✨ Key Features =

* **AI-Powered Generation**: Use OpenAI API (GPT-3.5, GPT-4, GPT-4 Turbo) or any OpenAI-compatible API
* **Automatic Schema Markup**: Generates valid FAQPage Schema.org JSON-LD automatically
* **SEO Plugin Integration**: Seamlessly integrates with RankMath and Yoast SEO
* **TinyMCE Button**: Generate FAQs directly from the WordPress editor
* **Schema Manager**: Visual metabox to edit, reorder, and manage FAQ schema
* **Multiple Output Formats**: HTML, Schema Only, or Both
* **Smart Shortcodes**: Use [content], [title], [excerpt] in prompts
* **Customizable**: Adjust temperature, model, token count, and more
* **No Coding Required**: User-friendly interface

= 🎯 Perfect For =

* Blog posts and articles
* Product pages (WooCommerce compatible)
* Service pages
* Landing pages
* Any content that benefits from FAQs

= 🔧 Technical Features =

* **RESTful API Support**: Works with any OpenAI-compatible API endpoint
* **Caching System**: Built-in caching to reduce API calls
* **Error Handling**: Comprehensive error messages and logging
* **Schema Validation**: Ensures valid JSON-LD output
* **SEO-Friendly HTML**: Clean, accessible markup
* **Responsive Design**: Mobile-friendly FAQ display
* **AJAX-Powered**: Fast, no page reload needed

= 🌍 Supported Languages =

* English
* Persian (Farsi) - RTL

= 🤝 SEO Plugin Compatibility =

* **RankMath**: Automatic FAQ schema injection into @graph
* **Yoast SEO**: Full integration with Yoast schema system
* **Standalone**: Works without SEO plugins too

= 📖 How It Works =

1. Install and activate the plugin
2. Go to Settings → FAQ Generator AI
3. Enter your OpenAI API key
4. Configure model and settings
5. Edit any post/page
6. Click "Generate FAQ" button in editor
7. FAQ HTML and Schema are automatically added
8. Publish your content with SEO-optimized FAQs

= 🔗 Links =

* [Support Forum](https://wordpress.org/support/plugin/faq-generator-ai/)
* [GitHub Repository](https://github.com/dadashzadeh/faq-generator-ai)

== Installation ==

= Automatic Installation =

1. Log in to your WordPress dashboard
2. Navigate to Plugins → Add New
3. Search for "FAQ Generator AI"
4. Click "Install Now"
5. Activate the plugin

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to WordPress dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the downloaded ZIP file
5. Click "Install Now"
6. Activate the plugin

= Configuration =

1. Go to Settings → FAQ Generator AI
2. Enter your OpenAI API key (required)
3. Configure model settings (default: gpt-3.5-turbo)
4. Set temperature, max tokens, and other options
5. Save settings
6. You're ready to generate FAQs!

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, you need an API key from OpenAI (https://platform.openai.com/api-keys). The plugin uses OpenAI's API to generate FAQ content. Alternative OpenAI-compatible APIs are also supported.

= Does it work with RankMath? =

Yes! The plugin automatically integrates FAQ schema with RankMath's JSON-LD output. No manual configuration needed.

= Is the schema valid? =

Yes, all schema output follows Schema.org FAQPage specification and is validated before output.

= Can I edit generated FAQs? =

Yes! Use the Schema Manager metabox to edit questions and answers, reorder items, or add/remove FAQs manually.

= Is there a limit on FAQ count? =

You can generate 3-10 FAQs per request. This is configurable in settings.

= Can I customize the output? =

Yes! Customize the prompt, temperature, model, output format, and HTML structure through settings.

= What happens if API fails? =

The plugin has comprehensive error handling with helpful messages. Check debug log if WP_DEBUG is enabled.

= Is it GDPR compliant? =

The plugin doesn't store personal data. API requests contain only your content. Review OpenAI's privacy policy for details.

== Screenshots ==

1. TinyMCE button in WordPress editor
2. FAQ Generator modal with settings
3. Generated FAQ HTML in editor
4. Schema Manager metabox
5. Plugin settings page
6. RankMath integration example
7. Frontend FAQ display
8. Mobile responsive design

== Changelog ==

= 1.1.0 - 2025-01-30 =
* Added: Multilingual support with automatic language detection
* Added: Schema Manager metabox for visual editing
* Added: Support for Markdown output format
* Improved: Better JSON parsing and validation
* Improved: Enhanced error handling and logging
* Fixed: Schema truncation issues with long content
* Fixed: RankMath @graph integration
* Fixed: Cache-related bugs
* Updated: UI/UX improvements in settings

== Third Party Services ==

This plugin uses the OpenAI API to generate FAQ content:

* Service: OpenAI API
* Website: https://openai.com
* Terms of Service: https://openai.com/terms
* Privacy Policy: https://openai.com/privacy

By using this plugin, you agree to OpenAI's terms and privacy policy. The plugin sends your content to OpenAI's servers for processing. No personal data is collected or stored by the plugin itself.

== Support ==

For support, please visit:

* [Support Forum](https://wordpress.org/support/plugin/faq-generator-ai/)
* [Contact Form](https://dadashzadeh.org/about-me)

== Contribute ==

This plugin is open source and available on GitHub:
https://github.com/dadashzadeh/faq-generator-ai

Pull requests and bug reports are welcome!
