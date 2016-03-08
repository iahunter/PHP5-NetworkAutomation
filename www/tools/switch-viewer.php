<?php
require_once "/etc/networkautomation/networkautomation.inc.php";

$HTML->breadcrumb("Home","/");
$HTML->breadcrumb("Tools","");
$HTML->breadcrumb("Switch Viewer",$HTML->thispage);

$HEAD_EXTRA = <<<EOT
	<style>
		div.content {
		    margin: 0px 0px;
		}
		#switchsearch {
		    margin: 10px 10px 5px 10px;
		}
		#tabs {
			border: none;
			padding: 0px 0px 0px 0px;
		}
		.ui-widget-content a { color: blue; }
		ul.ui-tabs-nav {
			border-top: 0px;
			border-left: 0px;
			border-right: 0px;
			background: transparent;
		}
		.ui-tabs .ui-tabs-nav {
			padding: 2px 9px 0;
		}
		.ui-tabs .ui-tabs-panel {
			padding: 10px 5px 5px 10px;
		}

		.ui-autocomplete-loading {
			background: white url('/images/ui-anim_basic_16x16.gif') right center no-repeat;
		}
		.ui-menu-item {
			list-style: none;
		}

		#tabs-min {
			background: transparent;
			border: none;
		}
		#tabs-min .ui-widget-header {
			background: transparent;
			border: none;
			border-bottom: 1px solid #c0c0c0;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border-radius: 0px;
		}
		#tabs-min .ui-state-default {
			background: transparent;
			border: none;
		}
		#tabs-min .ui-state-active {
			border: none;
		}
		#tabs-min .ui-state-default a {
			color: #c0c0c0;
		}
		#tabs-min .ui-state-active a {
			color: #459e00;
		}
	</style>
	<script type="text/javascript">
		$(function() {

			function log( message ) {
				$( "<div>" ).text( message ).prependTo( "#log" );
				$( "#log" ).scrollTop( 0 );
			}

			$( "#devices" ).autocomplete({
				source: "search.php",
				source: "/ajax/get_accessswitch_by_name.php",
				minLength: 1,
				select: function( event, ui ) {
					log( ui.item ?
					"Selected: " + ui.item.value + " aka " + ui.item.id :
					"Nothing selected, input was " + this.value );
					addTab('switch-view3.php?device=' + ui.item.id, ui.item.value);
				}
	    	});

			$[ "ui" ][ "autocomplete" ].prototype["_renderItem"] = function( ul, item) {
				return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( $( "<a></a>" ).html( item.label ) )
				.appendTo( ul );
			};

			var tabs = $( "#tabs" ).tabs({
				cache: false,

				beforeLoad: function(event, ui){
					var panel = $(ui.panel);
					if (panel.is(":empty")) {
						panel.append("<div class='tab-loading'><img src='/images/ui-anim_basic_16x16.gif' /> Connecting to switch, please wait...</div>")
					}
				},

				load: function(event, ui){
					$(ui.panel).find(".tab-loading").remove();
				}
			});

			tabs.find( ".ui-tabs-nav" ).sortable({
				axis: "x",
				stop: function() {
					$( "#tabs" ).tabs( "refresh" );
				}
			});

			function addTab(href,label) {
				var li = "<li><a href='" + href + "'>" + label + "</a><span class='ui-icon ui-icon-close'>Remove</span></li>";
				var li = "<li><a href='" + href + "'>" + label + "</a><button class='closebutton'>close</button></li>";
				tabs.find( ".ui-tabs-nav" ).append( li );
				tabs.tabs( "refresh" );
				var newtab = $( ".ui-tabs-nav" ).children().size() - 1;
				$("#tabs").tabs( "option","active", newtab );
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

			$('#tabs li').each(function(){
				var wd = (parseInt($(this).css('width').replace('px',''))+3)+'px';
				$(this).css('min-width',wd);
			});

		});
        </script>
EOT;

$HTML->set("HEAD_EXTRA",$HEAD_EXTRA);

print $HTML->header("Access Switch Viewer");

?>
	<div class="ui-widget" id="switchsearch">
	    <label for="devices">Switch Search:</label>
	    <input id="devices" />
	</div>
        <div id="tabs">
		<ul>
			<li><a href="#tabs-1">Switch Viewer 3.0</a><button class="closebutton">Close</button></li>
		</ul>
		<div id="tabs-1">
			<p>Welcome to the new switch viewer tool, please begin by typing the name of a switch in the box above.</p>
			<p>Reminder: If the switch you are looking for is not in the database, it is either not currently managable<br>
			(The tool cant access it via ssh with tacacs credentials) or has never had its configuration successfully audited.</p>
		</div>
        </div>
<?php
print $HTML->footer();
?>

