(()=>{"use strict";var e={408:(e,t,i)=>{i.d(t,{Z:()=>o});var a=i(1519),n=i.n(a)()((function(e){return e[1]}));n.push([e.id,'.fc_visual_modal .el-dialog{margin-top:0!important}.fc_builder_modal_wrap{align-items:center;background-color:rgba(0,0,0,.5);display:flex;inset:0;bottom:0;justify-content:center;left:0;position:fixed;right:0;top:0;z-index:99999}.fc_builder_modal_wrap .fc_visual_modal{background-color:#fff;height:100%;overflow:hidden;padding:0;position:relative;width:100%}.fc_builder_modal_wrap .fc_visual_modal iframe{height:100%;width:100%;z-index:9999}.fc_designer_wrapper iframe{min-height:calc(100vh - 150px);width:100%}.fc_design_template_visual_builder_wrapper .fc_composer_body{padding:0 15px;width:100%!important}.fc_visual_intro{background:#fff;margin:20px auto;max-width:600px;padding:30px;text-align:center}.fc_locked{overflow:hidden}.fc_visual_preview_inline{margin:0 auto;max-width:900px;position:relative}.fc_visual_preview_inline .fc_iframe_wrap{height:800px;overflow:hidden;position:relative;width:100%}.fc_visual_preview_inline .fc_iframe_wrap:before{background:rgba(68,67,67,.549);bottom:0;content:" ";left:0;position:absolute;right:0;top:0}.fc_visual_intro{box-shadow:0 0 12px 12px #464646;left:calc(50% - 225px);position:absolute;top:40px;z-index:9999}.fc_visual_parent{position:absolute;right:10px;top:12px;visibility:hidden;z-index:0}.fc_editor_header{box-shadow:1px 1px 3px hsla(0,1%,45%,.1);display:flex;justify-content:space-between;padding:8px 50px 7px 20px;position:relative}.fc_editor_header .fc_head_left img{height:32px;width:32px}.fc_editor_header .fc_head_left span{color:#000;font-size:10px;padding-left:5px;position:absolute;top:10px}.fc_editor_header .fc_head_right{align-items:center;display:flex}body.fc_locked_loaded.fc_locked .el-dialog{margin:0!important}body.fc_locked_loaded.fc_locked .el-dialog .fc_funnerl_editor>div{display:none!important;z-index:0!important}body.fc_locked_loaded.fc_locked .el-dialog .fc_funnerl_editor>div.fc_email_writer{display:block!important;z-index:999999!important}body.fc_locked_loaded.fc_locked .el-dialog .fluentcrm_visual_editor{margin:0!important}body.fc_locked_loaded.fc_locked .el-dialog .fluentcrm-sequence_control,body.fc_locked_loaded.fc_locked .el-dialog .fluentcrm_block_editor_body>form>div{display:none}body.fc_locked_loaded.fc_locked .el-dialog .fluentcrm_block_editor_body>form>div.fc_funnerl_editor{display:inherit}body.fc_locked_loaded.fc_locked .el-dialog .fc_design_template_visual_builder_wrapper .el-row>div{display:none}body.fc_locked_loaded.fc_locked .el-dialog .fc_design_template_visual_builder_wrapper .el-row>div.fc_composer_body{display:inherit}body.fc_locked_loaded div#wpwrap{z-index:0}.fc_visual_starter{margin:30px 0;text-align:center}.fc_visual_starter h1{color:#6e6e71;font-size:24px;margin-bottom:20px}.fc_visual_blocks{display:flex;margin:0 auto;max-width:1000px}.fc_visual_blocks .fc_visual_block{background:#fff;border:1px solid #e3e8ee;border-radius:4px;cursor:pointer;margin:20px;min-width:150px;text-align:center}.fc_visual_blocks .fc_visual_block img{border-top-left-radius:4px;border-top-right-radius:4px;max-width:100%}.fc_visual_blocks .fc_visual_block:hover{border:1px solid #3f9eff}.fc_visual_blocks .fc_visual_block:hover h3{color:#3f9eff}',""]);const o=n},1519:e=>{e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var i=e(t);return t[2]?"@media ".concat(t[2]," {").concat(i,"}"):i})).join("")},t.i=function(e,i,a){"string"==typeof e&&(e=[[null,e,""]]);var n={};if(a)for(var o=0;o<this.length;o++){var r=this[o][0];null!=r&&(n[r]=!0)}for(var s=0;s<e.length;s++){var l=[].concat(e[s]);a&&n[l[0]]||(i&&(l[2]?l[2]="".concat(i," and ").concat(l[2]):l[2]=i),t.push(l))}},t}},3379:(e,t,i)=>{var a,n=function(){return void 0===a&&(a=Boolean(window&&document&&document.all&&!window.atob)),a},o=function(){var e={};return function(t){if(void 0===e[t]){var i=document.querySelector(t);if(window.HTMLIFrameElement&&i instanceof window.HTMLIFrameElement)try{i=i.contentDocument.head}catch(e){i=null}e[t]=i}return e[t]}}(),r=[];function s(e){for(var t=-1,i=0;i<r.length;i++)if(r[i].identifier===e){t=i;break}return t}function l(e,t){for(var i={},a=[],n=0;n<e.length;n++){var o=e[n],l=t.base?o[0]+t.base:o[0],d=i[l]||0,c="".concat(l," ").concat(d);i[l]=d+1;var _=s(c),u={css:o[1],media:o[2],sourceMap:o[3]};-1!==_?(r[_].references++,r[_].updater(u)):r.push({identifier:c,updater:h(u,t),references:1}),a.push(c)}return a}function d(e){var t=document.createElement("style"),a=e.attributes||{};if(void 0===a.nonce){var n=i.nc;n&&(a.nonce=n)}if(Object.keys(a).forEach((function(e){t.setAttribute(e,a[e])})),"function"==typeof e.insert)e.insert(t);else{var r=o(e.insert||"head");if(!r)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");r.appendChild(t)}return t}var c,_=(c=[],function(e,t){return c[e]=t,c.filter(Boolean).join("\n")});function u(e,t,i,a){var n=i?"":a.media?"@media ".concat(a.media," {").concat(a.css,"}"):a.css;if(e.styleSheet)e.styleSheet.cssText=_(t,n);else{var o=document.createTextNode(n),r=e.childNodes;r[t]&&e.removeChild(r[t]),r.length?e.insertBefore(o,r[t]):e.appendChild(o)}}function f(e,t,i){var a=i.css,n=i.media,o=i.sourceMap;if(n?e.setAttribute("media",n):e.removeAttribute("media"),o&&"undefined"!=typeof btoa&&(a+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(o))))," */")),e.styleSheet)e.styleSheet.cssText=a;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(a))}}var p=null,m=0;function h(e,t){var i,a,n;if(t.singleton){var o=m++;i=p||(p=d(t)),a=u.bind(null,i,o,!1),n=u.bind(null,i,o,!0)}else i=d(t),a=f.bind(null,i,t),n=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(i)};return a(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;a(e=t)}else n()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=n());var i=l(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var a=0;a<i.length;a++){var n=s(i[a]);r[n].references--}for(var o=l(e,t),d=0;d<i.length;d++){var c=s(i[d]);0===r[c].references&&(r[c].updater(),r.splice(c,1))}i=o}}}}},t={};function i(a){var n=t[a];if(void 0!==n)return n.exports;var o=t[a]={id:a,exports:{}};return e[a](o,o.exports,i),o.exports}i.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return i.d(t,{a:t}),t},i.d=(e,t)=>{for(var a in t)i.o(t,a)&&!i.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:t[a]})},i.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),i.nc=void 0,(()=>{function e(e,t,i,a,n,o,r,s){var l,d="function"==typeof e?e.options:e;if(t&&(d.render=t,d.staticRenderFns=i,d._compiled=!0),a&&(d.functional=!0),o&&(d._scopeId="data-v-"+o),r?(l=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),n&&n.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(r)},d._ssrRegister=l):n&&(l=s?function(){n.call(this,(d.functional?this.parent:this).$root.$options.shadowRoot)}:n),l)if(d.functional){d._injectStyles=l;var c=d.render;d.render=function(e,t){return l.call(t),c(e,t)}}else{var _=d.beforeCreate;d.beforeCreate=_?[].concat(_,l):[l]}return{exports:e,options:d}}const t=e({name:"IframeBuilder",props:{preview_html:{type:String,default:function(){return""}},frame_height:{type:String,default:function(){return"500px"}}},data:function(){return{loading_preview:!0}},methods:{loadFrame:function(){var e=this.$refs.fc_ifr;(e.contentDocument||e.contentWindow.document).body.innerHTML=this.preview_html,this.loading_preview=!1}},mounted:function(){this.loadFrame()}},(function(){var e=this,t=e._self._c;return t("div",{staticClass:"fc_iframe_wrap"},[t("iframe",{directives:[{name:"show",rawName:"v-show",value:!e.loading_preview,expression:"!loading_preview"}],ref:"fc_ifr",staticStyle:{width:"100%",height:"500px"},style:{height:e.frame_height},attrs:{frameborder:"0",allowFullScreen:"",mozallowfullscreen:"",webkitallowfullscreen:""}})])}),[],!1,null,null,null).exports;var a=e({name:"inputPopoverDropdownExtended",props:{data:Array,close_on_insert:{type:Boolean,default:function(){return!0}},buttonText:{type:String,default:function(){return'Add SmartCodes <i class="el-icon-arrow-down el-icon--right"></i>'}},btnType:{type:String,default:function(){return"success"}},btn_ref:{type:String,default:function(){return"input-popover1"}},doc_url:{type:String,default:function(){return""}}},data:function(){return{activeIndex:0,visible:!1}},methods:{selectEmoji:function(e){this.insertShortcode(e.data)},insertShortcode:function(e){this.$emit("command",e),this.close_on_insert&&(this.visible=!1)}},mounted:function(){}},(function(){var e=this,t=e._self._c;return t("div",[t("el-popover",{ref:e.btn_ref,attrs:{placement:"right-end",offset:"50","popper-class":"fcrm-smartcodes-popover el-dropdown-list-wrapper",trigger:"click"},model:{value:e.visible,callback:function(t){e.visible=t},expression:"visible"}},[t("div",{staticClass:"el_pop_data_group"},[t("div",{staticClass:"el_pop_data_headings"},[t("ul",e._l(e.data,(function(i,a){return t("li",{key:a,class:e.activeIndex==a?"active_item_selected":"",attrs:{"data-item_index":a},on:{click:function(t){e.activeIndex=a}}},[e._v("\n                        "+e._s(i.title)+"\n                    ")])})),0),e._v(" "),e.doc_url?t("div",{staticClass:"pop_doc"},[t("a",{attrs:{href:e.doc_url,target:"_blank",rel:"noopener"}},[e._v("Learn More")])]):e._e()]),e._v(" "),t("div",{staticClass:"el_pop_data_body"},e._l(e.data,(function(i,a){return t("div",{key:a},[t("ul",{directives:[{name:"show",rawName:"v-show",value:e.activeIndex==a,expression:"activeIndex == current_index"}],class:"el_pop_body_item_"+a},e._l(i.shortcodes,(function(i,a){return t("li",{key:a,on:{click:function(t){return e.insertShortcode(a)}}},[e._v("\n                            "+e._s(i)),t("span",[e._v(e._s(a))])])})),0)])})),0)])]),e._v(" "),t("el-button-group",[t("el-button",{directives:[{name:"popover",rawName:"v-popover",value:e.btn_ref,expression:"btn_ref"}],staticClass:"editor-add-shortcode",attrs:{size:"mini",type:e.btnType},domProps:{innerHTML:e._s(e.buttonText)}})],1)],1)}),[],!1,null,null,null);function n(e){return function(e){if(Array.isArray(e))return o(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(!e)return;if("string"==typeof e)return o(e,t);var i=Object.prototype.toString.call(e).slice(8,-1);"Object"===i&&e.constructor&&(i=e.constructor.name);if("Map"===i||"Set"===i)return Array.from(e);if("Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i))return o(e,t)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function o(e,t){(null==t||t>e.length)&&(t=e.length);for(var i=0,a=new Array(t);i<t;i++)a[i]=e[i];return a}function r(e){return function(e){if(Array.isArray(e))return s(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(!e)return;if("string"==typeof e)return s(e,t);var i=Object.prototype.toString.call(e).slice(8,-1);"Object"===i&&e.constructor&&(i=e.constructor.name);if("Map"===i||"Set"===i)return Array.from(e);if("Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i))return s(e,t)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function s(e,t){(null==t||t>e.length)&&(t=e.length);for(var i=0,a=new Array(t);i<t;i++)a[i]=e[i];return a}const l={name:"VisualEditor",props:["value","campaign","extra_tags"],components:{IframeBuilder:t,MergeCodes:e({name:"MergeCodes",components:{popover:a.exports},props:["extra_tags"],data:function(){return{editorShortcodes:[]}},methods:{handleCommand:function(e){this.copyItem(e)},copyItem:function(e){this.copy_success=!1;var t=!1;if(window.clipboardData&&window.clipboardData.setData)window.clipboardData.clipboardData.setData("Text",e),t=!0;else if(document.queryCommandSupported&&document.queryCommandSupported("copy")){var i=document.createElement("textarea");i.textContent=e,i.style.position="fixed",document.body.appendChild(i),i.select();try{document.execCommand("copy"),t=!0}catch(e){console.warn("Copy to clipboard failed.",e),t=!1}finally{document.body.removeChild(i)}}t?(this.copy_success=!0,this.$notify({message:this.$t("Smartcode has been copied to your clipboard"),position:"bottom-right",customClass:"bottom_right fc_notify_z",type:"success"})):this.$notify({message:this.$t("Your Browser does not support JS copy. Please copy manually"),position:"bottom-right",customClass:"bottom_right",type:"error"})}},mounted:function(){var e,t,i;((e=this.editorShortcodes).push.apply(e,n(window.fcAdmin.globalSmartCodes)),(t=this.editorShortcodes).push.apply(t,n(window.fcAdmin.extendedSmartCodes)),this.extra_tags&&this.extra_tags.length)&&(i=this.editorShortcodes).push.apply(i,n(this.extra_tags))}},(function(){var e=this;return(0,e._self._c)("popover",{staticClass:"popover-wrapper",staticStyle:{display:"inline-block"},attrs:{doc_url:"https://fluentcrm.com/docs/merge-codes-smart-codes-usage/",btnType:"text",buttonText:"{{ }}",data:e.editorShortcodes},on:{command:e.handleCommand}})}),[],!1,null,null,null).exports,LoaderSkeleton:e({name:"LoaderSkeleton"},(function(){var e=this,t=e._self._c;return t("div",{staticStyle:{padding:"20px"}},[t("el-row",{attrs:{gutter:30}},[t("el-col",{attrs:{span:5}},[e._v(".")]),e._v(" "),t("el-col",{attrs:{span:9}},[t("el-skeleton",{attrs:{animated:""}},[t("template",{slot:"template"},[t("el-skeleton-item",{staticStyle:{height:"240px"},attrs:{variant:"image"}}),e._v(" "),t("el-skeleton-item",{staticStyle:{width:"50%",margin:"20px auto"},attrs:{variant:"h3"}}),e._v(" "),t("div",{staticStyle:{padding:"14px","text-align":"left"}},[t("div",{staticStyle:{"text-align":"center"}},[t("el-skeleton-item",{staticStyle:{width:"30%"},attrs:{variant:"text"}}),e._v(" "),t("el-skeleton-item",{attrs:{variant:"text"}}),e._v(" "),t("el-skeleton-item",{attrs:{variant:"text"}})],1),e._v(" "),t("el-row",{attrs:{gutter:30}},[t("el-col",{attrs:{span:12}},[t("el-skeleton-item",{staticStyle:{height:"140px",margin:"20px 0"},attrs:{variant:"image"}})],1),e._v(" "),t("el-col",{attrs:{span:12}},[t("el-skeleton-item",{staticStyle:{height:"140px",margin:"20px 0"},attrs:{variant:"image"}})],1)],1),e._v(" "),t("div",{staticStyle:{"text-align":"left"}},[t("el-skeleton-item",{attrs:{variant:"text"}}),e._v(" "),t("el-skeleton-item",{attrs:{variant:"text"}}),e._v(" "),t("el-skeleton-item",{staticStyle:{width:"30%"},attrs:{variant:"text"}})],1)],1)],1)],2)],1),e._v(" "),t("el-col",{attrs:{span:5}},[e._v(".")]),e._v(" "),t("el-col",{attrs:{span:4}},[t("el-skeleton",{attrs:{rows:12,animated:""}}),e._v(" "),t("el-skeleton",{attrs:{animated:"",rows:3}})],1),e._v(" "),t("el-col",{attrs:{span:1}},[t("el-skeleton",{attrs:{rows:6}})],1)],1)],1)}),[],!1,null,null,null).exports,DisplayCondition:e({name:"DisplayCondition",props:["existing_tag","editing_condition"],data:function(){return{form:{selected_tags:[],display_type:"show_if_tag_exist"},loading:!1}},computed:{tags:function(){var e={};return this.each(this.appVars.available_tags,(function(t){e[t.id]=t})),e}},methods:{fireCondition:function(){var e=this,t=[];if(this.each(this.form.selected_tags,(function(i){e.tags[i]?t.push(e.tags[i].title):delete e.form.selected_tags[i]})),this.form.selected_tags&&this.form.selected_tags.length){var i=this.$t("Show if in tags:");"show_if_tag_not_exist"==this.form.display_type&&(i=this.$t("Show if not in tags:"));var a={type:"check_contact_tag",label:i,description:t.join(", "),before:"[fc_vis_cond type='"+this.form.display_type+"' values='"+this.form.selected_tags.join("|")+"']",after:"[/fc_vis_cond]"};this.$emit("insertTag",a)}else this.$notify.error(this.$t("Please select at least one tag"))},parseExitingCondition:function(){if(this.editing_condition&&this.editing_condition.before){this.loading=!0;var e=this.editing_condition.before.match(/\[fc_vis_cond ([^\]]*)\]/)[1];e=e.split(" ");var t={};for(var i in e){var a=e[i].split("='");t[a[0]]=a[1].replace(/'/g,"")}t.type&&(this.form.display_type=t.type),t.values&&(this.form.selected_tags=t.values.split("|")),this.loading=!1}}},mounted:function(){this.parseExitingCondition()}},(function(){var e=this,t=e._self._c;return t("div",[t("el-form",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],attrs:{"label-position":"top",model:e.form}},[t("el-form-item",{attrs:{label:e.$t("CONDITION TYPE")}},[t("el-radio-group",{model:{value:e.form.display_type,callback:function(t){e.$set(e.form,"display_type",t)},expression:"form.display_type"}},[t("el-radio",{attrs:{label:"show_if_tag_exist"}},[e._v(e._s(e.$t("Show IF in Selected Tag")))]),e._v(" "),t("el-radio",{attrs:{label:"show_if_tag_not_exist"}},[e._v(e._s(e.$t("Show IF not in selected tag")))])],1)],1),e._v(" "),t("el-form-item",{attrs:{label:e.$t("Select Targeted Tags")}},[t("el-checkbox-group",{model:{value:e.form.selected_tags,callback:function(t){e.$set(e.form,"selected_tags",t)},expression:"form.selected_tags"}},e._l(e.tags,(function(i){return t("el-checkbox",{key:i.slug,attrs:{label:i.id}},[e._v(e._s(i.title))])})),1)],1)],1),e._v(" "),t("el-button",{attrs:{type:"primary"},on:{click:function(t){return e.fireCondition()}}},[e._v(e._s(e.$t("Apply Condition")))])],1)}),[],!1,null,null,null).exports},data:function(){return{is_active:!1,is_loading:!1,editor_loaded:!1,editor_type:"old",frame_url:"",isVerified:!1,target_origin:window.fcVisualVars.editor_domain,context:this.$route.name,inlineCallBack:null,predefinedTemplates:[{id:243318,name:"Blank",image:this.appVars.images_url+"/templates/blank.jpg"},{id:228634,name:"Standard",image:this.appVars.images_url+"/templates/standard.jpg"},{id:249301,name:"Sales",image:this.appVars.images_url+"/templates/sales.jpg"}],loadedtemplateId:243318,showDisplayCondition:!1,editing_condition:{}}},computed:{mergeTags:function(){var e=this,t={},i=[].concat(r(window.fcAdmin.globalSmartCodes),r(window.fcAdmin.extendedSmartCodes));return this.extra_tags&&this.extra_tags.length&&(i=[].concat(r(i),r(this.extra_tags))),this.each(i,(function(i){t[i.key]||(t[i.key]={name:i.title,mergeTags:{}});var a=1;e.each(i.shortcodes,(function(e,n){t[i.key].mergeTags[a+"_"+n]={name:e,value:n},a++}))})),t},is_inline:function(){return"edit_template"==this.context||"campaign"==this.context||"edit-sequence-email"==this.context}},methods:{loadingFrame:function(){document.body.classList.add("fc_locked"),this.is_loading=!0,this.is_active=!0;var e=document.getElementById("fc_visual_frame");this.isVerified||(e.contentWindow.postMessage({from:"fc_parent",action:"verify"},this.target_origin),this.isVerified=!0)},iframeEvent:function(e){var t=this,i=e.data;if("fc_editor"==i.type)if("editor_loaded"==i.action){document.body.classList.add("fc_locked_loaded");var a=document.getElementById("fc_visual_frame"),n=this.getInitialContent();n?a.contentWindow.postMessage({from:"fc_parent",action:"load_design",data:n,mergeTags:this.mergeTags},this.target_origin):a.contentWindow.postMessage({from:"fc_parent",action:"load_template",template_id:this.loadedtemplateId,mergeTags:this.mergeTags},this.target_origin),setTimeout((function(){t.is_loading=!1,t.editor_loaded=!0}),1e3)}else if("save_design"==i.action)this.saveContent(i),this.$notify({message:this.$t("Saved"),position:"bottom-right",customClass:"fc_notify_z bottom_right",type:"success",duration:500});else if("save_close"==i.action)this.saveContent(i),document.body.classList.remove("fc_locked","fc_locked_loaded"),this.is_active=!1,"edit_funnel"==this.context&&(this.frame_url="",this.$nextTick((function(){t.setFrameUrl()})));else if("image_selector"==i.action)this.initUploader();else if("open_merge_codes"==i.action)jQuery("#fc_merge_code_wrap button").trigger("click");else if("updated_design"==i.action)this.saveContent(i),this.inlineCallBack&&this.inlineCallBack(i);else if("display_condition"==i.action){if(!window.fcVisualVars.has_conditions)return void this.$notify.error(this.$t("Please update FluentCRM Pro first"));this.initDisplayCondition(i.items)}else console.log(i)},initDisplayCondition:function(e){this.editing_condition=e,this.showDisplayCondition=!0},loadTemplate:function(e,t){var i=this,a=document.getElementById("fc_visual_frame");this.loadedtemplateId=e,a.contentWindow.postMessage({from:"fc_parent",action:"load_template",template_id:e,mergeTags:this.mergeTags},this.target_origin),this.editor_type="old",t?this.loadingFrame():setTimeout((function(){i.is_loading=!1,i.editor_loaded=!0}),1e3)},saveContent:function(e){var t=this;this.campaign._visual_builder_design=e.design,this.$emit("input",e.html),"update_only"!=e.reference&&this.$nextTick((function(){t.$emit("save")})),this.editor_type="old"},getInitialContent:function(){return this.campaign._visual_builder_design||null},initUploader:function(){wp.media.editor.remove("fc_launch_editor_button");var e=wp.media.editor.send.attachment,t=this;return wp.media.editor.send.attachment=function(i,a){var n=document.getElementById("fc_visual_frame"),o={url:a.url,width:a.width,height:a.height};"full"!=i.size&&a.sizes&&a.sizes[i.size]&&(a.sizes[i.size].width<1e3&&(i.size="large"),a.sizes[i.size]&&a.sizes[i.size].width>1e3&&(o=a.sizes[i.size])),n.contentWindow.postMessage({from:"fc_parent",action:"add_media",media:o},t.target_origin),wp.media.editor.send.attachment=e},wp.media.editor.open("fc_launch_editor_button",{frame:"post",state:"insert",title:this.$t("Select Image for Your Email Body"),multiple:!1}),!1},setFrameUrl:function(){if(!window.fcVisualVars)return"";var e=new URL(window.fcVisualVars.url);this.each(window.fcVisualVars.params,(function(t,i){e.searchParams.set(i,t)})),e.searchParams.set("context",this.$route.name),e.searchParams.set("version",this.appVars.app_version),this.appVars.disable_ai&&e.searchParams.set("disable_ai","yes"),this.frame_url=e.href},listenBus:function(e){this.editor_loaded?(e.callback?this.inlineCallBack=e.callback:this.inlineCallBack=null,document.getElementById("fc_visual_frame").contentWindow.postMessage({from:"fc_parent",action:"fire_save_data",reference:e.reference},this.target_origin)):this.$notify.error(this.$t("Editor is loading. Please wait"))},fireConditionTag:function(e){document.getElementById("fc_visual_frame").contentWindow.postMessage({from:"fc_parent",action:"apply_condition",item:e},this.target_origin),this.showDisplayCondition=!1}},mounted:function(){this.setFrameUrl(),this.campaign._visual_builder_design?this.editor_type="old":!this.value||-1!==this.value.indexOf(this.$t("Start Writing Here"))||jQuery(this.value).text().trim().length<20?this.editor_type="new":this.editor_type="existing_content",window.addEventListener("message",this.iframeEvent,!1),window.wpActiveEditor||(window.wpActiveEditor=null),this.$bus.$on("getVisualData",this.listenBus)},beforeDestroy:function(){window.removeEventListener("message",this.iframeEvent,!1),document.body.classList.remove("fc_locked","fc_locked_loaded"),this.$bus.$off("getVisualData",this.listenBus)}};var d=i(3379),c=i.n(d),_=i(408),u={insert:"head",singleton:!1};c()(_.Z,u);_.Z.locals;const f=e(l,(function(){var e=this,t=e._self._c;return t("div",[e.is_inline?t("div",["new"==e.editor_type?t("div",{staticClass:"fc_visual_starter"},[t("h1",[e._v(e._s(e.$t("Select a starter design to build your email")))]),e._v(" "),t("div",{staticClass:"fc_visual_blocks"},e._l(e.predefinedTemplates,(function(i){return t("div",{key:i.id,staticClass:"fc_visual_block",on:{click:function(t){return e.loadTemplate(i.id)}}},[t("img",{attrs:{src:i.image,alt:i.name}}),e._v(" "),t("h3",[e._v(e._s(i.name))])])})),0)]):e.editor_loaded?e._e():t("div",{staticStyle:{background:"white"}},[t("loader-skeleton")],1),e._v(" "),e.frame_url?t("iframe",{staticStyle:{width:"100%","min-height":"100vh"},style:{visibility:e.editor_loaded&&"new"!=e.editor_type?"visible":"hidden"},attrs:{id:"fc_visual_frame",src:e.frame_url+"&inline=yes"}}):e._e()]):t("div",{staticClass:"fc_visual_builder_wrap"},[e.is_active?e._e():t("div",{staticClass:"fc_visual_wrap"},[t("div",{staticClass:"fc_visual_preview_inline"},[t("div",{staticClass:"fc_visual_intro"},[t("h3",[e._v(e._s(e.$t("Visually design your email with Drag & Drop Builder")))]),e._v(" "),"new"==e.editor_type?t("div",{staticClass:"fc_visual_starter"},[t("h1",[e._v(e._s(e.$t("Select a starter design to build your email")))]),e._v(" "),t("div",{staticClass:"fc_visual_blocks"},e._l(e.predefinedTemplates,(function(i){return t("div",{key:i.id,staticClass:"fc_visual_block",on:{click:function(t){return e.loadTemplate(i.id,!0)}}},[t("img",{attrs:{src:i.image,alt:i.name}}),e._v(" "),t("h3",[e._v(e._s(i.name))])])})),0)]):t("el-button",{attrs:{id:"fc_launch_editor_button",type:"primary"},on:{click:function(t){return e.loadingFrame()}}},[e._v(" "+e._s(e.$t("Launch Visual Editor"))+"\n                    ")]),e._v(" "),e.editor_loaded?e._e():t("p",{staticStyle:{position:"absolute",right:"2px"}},[t("span",{staticClass:"el-icon el-icon-loading"})])],1),e._v(" "),e.is_active?e._e():t("iframe-builder",{attrs:{frame_height:"800px",preview_html:e.value}})],1)]),e._v(" "),t("div",{directives:[{name:"show",rawName:"v-show",value:e.is_active,expression:"is_active"}],staticClass:"fc_builder_modal_wrap"},[t("div",{staticClass:"fc_visual_modal"},[e.editor_loaded?e._e():t("div",[t("div",{staticClass:"fc_editor_header"},[t("div",{staticClass:"fc_head_left"},[t("img",{attrs:{src:e.appVars.images_url+"/fluentcrm-logo.svg"}}),e._v(" "),t("span",[e._v("Pro")])]),e._v(" "),t("div",{staticClass:"fc_head_right"},[t("el-button",{attrs:{type:"primary",size:"small",disabled:!0}},[e._v(e._s(e.$t("Save")))]),e._v(" "),t("el-button",{attrs:{type:"danger",size:"small",disabled:!0}},[e._v(e._s(e.$t("Save & close")))])],1)]),e._v(" "),t("loader-skeleton")],1),e._v(" "),t("div",{staticClass:"fc_visual_parent"},[t("merge-codes",{attrs:{id:"fc_merge_code_wrap",extra_tags:e.extra_tags}})],1),e._v(" "),e.frame_url?t("iframe",{staticStyle:{width:"100%"},style:{visibility:e.editor_loaded?"visible":"hidden"},attrs:{id:"fc_visual_frame",src:e.frame_url}}):e._e()])])]),e._v(" "),e.showDisplayCondition?[t("el-dialog",{staticClass:"fc_force_modal",attrs:{title:"Select your Condition",visible:e.showDisplayCondition,"append-to-body":!0,"modal-append-to-body":!0,width:"30%"},on:{"update:visible":function(t){e.showDisplayCondition=t}}},[t("display-condition",{attrs:{editing_condition:e.editing_condition},on:{insertTag:e.fireConditionTag}})],1)]:e._e()],2)}),[],!1,null,null,null).exports;window.FLUENTCRM.Vue.component("VisualEmailBuilder",f)})()})();