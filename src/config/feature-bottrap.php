{
  "slug":        "bottrap",
  "properties":  {
    "slug":                  "bottrap",
    "storage_key":           "bottrap",
    "name":                  "Bot Trap",
    "show_module_menu_item": false,
    "show_module_options":   true,
    "tagline":               "Identify, Trap and Block Bots",
    "show_central":          true,
    "access_restricted":     true,
    "premium":               true,
    "run_if_whitelisted":    false,
    "run_if_verified_bot":   false,
    "order":                 30
  },
  "sections":    [
    {
      "slug":        "section_probes",
      "primary":     true,
      "title":       "Capture Probing Bots",
      "title_short": "Probing Bots",
      "summary":     [
        "Recommendation - Enable to capture bots/spiders that don't honour 'nofollow' directives."
      ]
    },
    {
      "slug":        "section_logins",
      "title":       "Capture Login Bots",
      "title_short": "Login Bots",
      "summary":     [
        "Recommendation - Enable to capture bots/spiders that don't honour 'nofollow' directives."
      ]
    },
    {
      "slug":        "section_behaviours",
      "title":       "Identify Common Bot Behaviours",
      "title_short": "Bot Behaviours",
      "summary":     [
        "Recommendation - Enable to capture bots/spiders that don't honour 'nofollow' directives."
      ]
    },
    {
      "slug":        "section_enable_plugin_feature_bottrap",
      "title":       "Enable Module: BotTrap",
      "title_short": "Enable Module",
      "summary":     [
        "Purpose - BotTrap monitors a typical set of bot behaviours to help identify probing bots.",
        "Recommendation - Enable as many mouse traps as possible."
      ]
    },
    {
      "slug":   "section_non_ui",
      "hidden": true
    }
  ],
  "options":     [
    {
      "key":         "enable_bottrap",
      "section":     "section_enable_plugin_feature_bottrap",
      "default":     "Y",
      "type":        "checkbox",
      "link_info":   "",
      "link_blog":   "",
      "name":        "Enable BotTrap",
      "summary":     "Enable (or Disable) The BotTrap module",
      "description": "Un-Checking this option will completely disable the BotTrap module"
    },
    {
      "key":           "404_detect",
      "section":       "section_probes",
      "default":       "disabled",
      "type":          "select",
      "value_options": [
        {
          "value_key": "disabled",
          "text":      "Disabled"
        },
        {
          "value_key": "transgression",
          "text":      "Increment Transgression"
        },
        {
          "value_key": "block",
          "text":      "Immediate Block"
        }
      ],
      "link_info":     "",
      "link_blog":     "",
      "name":          "404 Detect",
      "summary":       "Identify A Bot When It Hits A 404",
      "description":   "Detect When A Visitor Browses To A Non-Existent Page."
    },
    {
      "key":           "link_cheese",
      "section":       "section_probes",
      "default":       "transgression",
      "type":          "select",
      "value_options": [
        {
          "value_key": "disabled",
          "text":      "Disabled"
        },
        {
          "value_key": "transgression",
          "text":      "Increment Transgression"
        },
        {
          "value_key": "block",
          "text":      "Immediate Block"
        }
      ],
      "link_info":     "",
      "link_blog":     "",
      "name":          "Link Cheese",
      "summary":       "Tempt A Bot With A Link To Follow",
      "description":   "Detect A Bot That Follows A 'no-follow' Link."
    },
    {
      "key":           "xmlrpc",
      "section":       "section_probes",
      "default":       "disabled",
      "type":          "select",
      "value_options": [
        {
          "value_key": "disabled",
          "text":      "Disabled"
        },
        {
          "value_key": "transgression",
          "text":      "Increment Transgression"
        },
        {
          "value_key": "block",
          "text":      "Immediate Block"
        }
      ],
      "link_info":     "",
      "link_blog":     "",
      "name":          "XML-RPC Access",
      "summary":       "Identify A Bot When It Accesses XML-RPC",
      "description":   "If you don't use XML-RPC, why would anyone access it?"
    },
    {
      "key":           "invalid_username",
      "section":       "section_logins",
      "default":       "transgression",
      "type":          "select",
      "value_options": [
        {
          "value_key": "disabled",
          "text":      "Disabled"
        },
        {
          "value_key": "transgression",
          "text":      "Increment Transgression"
        },
        {
          "value_key": "block",
          "text":      "Immediate Block"
        }
      ],
      "link_info":     "",
      "link_blog":     "",
      "name":          "Invalid Usernames",
      "summary":       "Detect Invalid Username Logins",
      "description":   "Identify A Bot When It Tries To Login With A Non-Existent Username."
    },
    {
      "key":           "failed_login",
      "section":       "section_logins",
      "default":       "transgression",
      "type":          "select",
      "value_options": [
        {
          "value_key": "disabled",
          "text":      "Disabled"
        },
        {
          "value_key": "transgression",
          "text":      "Increment Transgression"
        },
        {
          "value_key": "block",
          "text":      "Immediate Block"
        }
      ],
      "link_info":     "",
      "link_blog":     "",
      "name":          "Failed Login",
      "summary":       "Detect Failed Login Attempts By Valid Usernames",
      "description":   "Penalise a visitor who fails to login using a valid username."
    },
    {
      "key":           "fake_webcrawler",
      "section":       "section_behaviours",
      "default":       "transgression",
      "type":          "select",
      "value_options": [
        {
          "value_key": "disabled",
          "text":      "Disabled"
        },
        {
          "value_key": "transgression",
          "text":      "Increment Transgression"
        },
        {
          "value_key": "block",
          "text":      "Immediate Block"
        }
      ],
      "link_info":     "",
      "link_blog":     "",
      "name":          "Fake Web Crawler",
      "summary":       "Detect Fake Search Engine Crawlers",
      "description":   "Identify a Bot when it presents as an official web crawler, but analysis shows it's fake."
    },
    {
      "key":          "insights_last_firewall_block_at",
      "transferable": false,
      "section":      "section_non_ui",
      "default":      0
    }
  ],
  "definitions": {
  }
}