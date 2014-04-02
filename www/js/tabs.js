$(function() {

	var tabs = $( ".tabs" ).tabs({
		cache: false,

		beforeLoad: function(event, ui){
			var panel = $(ui.panel);
			if (panel.is(":empty")) {
				panel.append("<div class='tab-loading'><img src='/images/ui-anim_basic_16x16.gif' /> loading, please wait...</div>")
			}
		},

		load: function(event, ui){
			$(ui.panel).find(".tab-loading").remove();
		}
	});

	tabs.find( ".ui-tabs-nav" ).sortable({
		axis: "x",
		stop: function() {
			$( ".tabs" ).tabs( "refresh" );
		}
	});

	function addTab(href,label) {
		var li = "<li><a href='" + href + "'>" + label + "</a><span class='ui-icon ui-icon-close'>Remove</span></li>";
		var li = "<li><a href='" + href + "'>" + label + "</a><button class='closebutton'>close</button></li>";
		tabs.find( ".ui-tabs-nav" ).append( li );
		tabs.tabs( "refresh" );
		var newtab = $( ".ui-tabs-nav" ).children().size() - 1;
		$(".tabs").tabs( "option","active", newtab );
		$( ".closebutton" ).button({
			icons: {
				primary: "ui-icon-close"
			},
			text: false
		});
		log( "Added LI: " + li );
	}

	tabs.delegate( "span.ui-icon-close", "click", function() {
		var panelId = $( this ).closest( "li" ).remove().attr( "aria-controls" );
		$( "#" + panelId ).remove();
		tabs.tabs( "refresh" );
	});

	tabs.delegate( "button.closebutton", "click", function() {
		var panelId = $( this ).closest( "li" ).remove().attr( "aria-controls" );
		$( "#" + panelId ).remove();
		tabs.tabs( "refresh" );
	});

	$( "button" ).button({
		icons: {
			primary: "ui-icon-close"
		},
		text: false
	});

	$('.tabs li').each(function(){
		var wd = (parseInt($(this).css('width').replace('px',''))+3)+'px';
		$(this).css('min-width',wd);
	});

});
