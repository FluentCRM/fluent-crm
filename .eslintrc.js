// If you want to customize please check this
// https://simonkollross.de/posts/using-eslint-with-vuejs-and-laravel-mix
// To Customize .vue File Rules: https://github.com/vuejs/eslint-plugin-vue#bulb-rules
module.exports = {
    "parser": "vue-eslint-parser",
    "parserOptions": {
        "parser": "babel-eslint",
        "ecmaVersion": 8,
        "sourceType": "module"
    },
    "extends": [
        "standard",
        "plugin:vue/essential",
    ],
    "globals": {
        "jQuery": false,
        "wp": false,
    },
    "rules": {
        "vue/max-attributes-per-line": "off",
        "indent": ["error", 4],
        "semi": "off",
        "object-curly-spacing": "off",
        "dot-notation": "off",
        "vue/no-use-v-if-with-v-for": "off",
        "no-trailing-spaces": "off",
        "no-new": "off",
        "space-before-function-paren": "off",
        "vue/script-indent": "off",
        "vue/no-textarea-mustache": "off",
        "vue/html-indent": "off",
        "no-prototype-builtins": "off",
        "eqeqeq": "off"
    },
    "overrides": [
        {
            "files": ["*.vue"],
            "rules": {
                "indent": "off"
            }
        }
    ]
};
