/*
 * Very simple jQuery Color Picker
 * https://github.com/tkrotoff/jquery-simplecolorpicker
 *
 * Copyright (C) 2012-2013 Tanguy Krotoff <tkrotoff@gmail.com>
 *
 * Licensed under the MIT license
 */!function(e){"use strict";var t=function(e,t){this.init("simplecolorpicker",e,t)};t.prototype={constructor:t,init:function(t,o,i){var s=this;if(s.type=t,s.$select=e(o),s.$select.hide(),s.options=e.extend({},e.fn.simplecolorpicker.defaults,i),s.$colorList=null,s.options.picker===!0){var c=s.$select.find("> option:selected").text();s.$icon=e('<span class="simplecolorpicker icon" title="'+c+'" style="background-color:'+s.$select.val()+'" role="button" tabindex="0"></span>').insertAfter(s.$select),s.$icon.on("click."+s.type,e.proxy(s.showPicker,s)),s.$picker=e('<span class="simplecolorpicker picker '+s.options.theme+'"></span>').appendTo(document.body),s.$colorList=s.$picker,e(document).on("mousedown."+s.type,e.proxy(s.hidePicker,s)),s.$picker.on("mousedown."+s.type,e.proxy(s.mousedown,s))}else s.$inline=e('<span class="simplecolorpicker inline '+s.options.theme+'"></span>').insertAfter(s.$select),s.$colorList=s.$inline;s.$select.find("> option").each(function(){var t=e(this),o=t.val(),i=t.is(":selected"),c=t.is(":disabled"),r="";i===!0&&(r=" data-selected");var n="";c===!0&&(n=" data-disabled");var l="";c===!1&&(l=' title="'+t.text()+'"');var p="";c===!1&&(p=' role="button" tabindex="0"');var a=e('<span class="color"'+l+' style="background-color:'+o+'" data-color="'+o+'"'+r+n+p+'></span>');s.$colorList.append(a),a.on("click."+s.type,e.proxy(s.colorSpanClicked,s));var d=t.next();d.is("optgroup")===!0&&s.$colorList.append('<span class="vr"></span>')})},selectColor:function(t){var o=this,i=o.$colorList.find("> span.color").filter(function(){return e(this).data("color").toLowerCase()===t.toLowerCase()});i.length>0?o.selectColorSpan(i):console.error("The given color '"+t+"' could not be found")},showPicker:function(){var e=this.$icon.offset();this.$picker.css({left:e.left-6,top:e.top+this.$icon.outerHeight()}),this.$picker.show(this.options.pickerDelay)},hidePicker:function(){this.$picker.hide(this.options.pickerDelay)},selectColorSpan:function(e){var t=e.data("color"),o=e.prop("title");e.siblings().removeAttr("data-selected"),e.attr("data-selected",""),this.options.picker===!0&&(this.$icon.css("background-color",t),this.$icon.prop("title",o),this.hidePicker()),this.$select.val(t)},colorSpanClicked:function(t){e(t.target).is("[data-disabled]")===!1&&(this.selectColorSpan(e(t.target)),this.$select.trigger("change"))},mousedown:function(e){e.stopPropagation(),e.preventDefault()},destroy:function(){this.options.picker===!0&&(this.$icon.off("."+this.type),this.$icon.remove(),e(document).off("."+this.type)),this.$colorList.off("."+this.type),this.$colorList.remove(),this.$select.removeData(this.type),this.$select.show()}},e.fn.simplecolorpicker=function(o){var i=e.makeArray(arguments);return i.shift(),this.each(function(){var s=e(this),c=s.data("simplecolorpicker"),r="object"==typeof o&&o;void 0===c&&s.data("simplecolorpicker",c=new t(this,r)),"string"==typeof o&&c[o].apply(c,i)})},e.fn.simplecolorpicker.defaults={theme:"fontawesome",picker:!1,pickerDelay:0}}(jQuery);