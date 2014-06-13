$("a#toggleMenu").click(function() {
	$("body").toggleClass("mobileNav");
});

$("a#toggleSearch").click(function() {
	$("body").toggleClass("mobileSearch");
	$("#searchBoxFront").find("input").focus();
});


$(this).parent().siblings('div.bottom').find("input.post").focus();


$("a#sectionNavButton").click(function() {
	$(this).next().toggleClass("active");
});

/*
$("li.select-language").click(function(event) {

	event.stopPropagation()

	console.log("clicked");
	
	current_language = $(".lang-selected").text();
	
	new_language = "<span>" + $(this).data("language")  + "</span>";
	
	//console.log(new_language);
		
	if(current_language.indexOf(new_language) == -1) { 
		$(".lang-selected").append(new_language);
	} else {
		console.log($(".lang-selected").text());
		$(".lang-selected").text().replace(new_language, '');
	}
	
});
*/

// functie demo language switch
/* WME (I commented-out checkboxes in header.php too)
$("li.select-language").click(function(event) {

	event.stopPropagation()
	
	if( $("#nederlands").is(":checked") && $("#english").is(":checked") )
	{
		$(".lang-selected").text('NL/EN');
		$("header#mainHeader h1#idTag a").css('background-image', 'url(../skins/deltaskin/img/logo-deltaexpertise-nl.png)');
	} 
	else if( $("#nederlands").is(":checked") )
	{
		$(".lang-selected").text('NL');
		$("header#mainHeader h1#idTag a").css('background-image', 'url(../skins/deltaskin/img/logo-deltaexpertise-nl.png)');
	}
	else if( $("#english").is(":checked") )
	{
		$(".lang-selected").text('EN');
		$("header#mainHeader h1#idTag a").css('background-image', 'url(../skins/deltaskin/img/logo-deltaexpertise-en.png)');
	} 
	else
	{
		$(".lang-selected").text('NL');
		$("header#mainHeader h1#idTag a").css('background-image', 'url(../skins/deltaskin/img/logo-deltaexpertise-nl.png)');		
	}
	
});*/

//WME quick fix for logo dependent on selected language
//alert($(".lang-selected").text());
var scriptpath = $("script[src]").last().attr("src").split('?')[0].split('/').slice(0, -1).join('/')+'/';
var nllogo = scriptpath+'skins/deltaskin/img/logo-deltaexpertise-nl.png';
var enlogo = scriptpath+'skins/deltaskin/img/logo-deltaexpertise-en.png';
if( $(".lang-selected").text()=='nl')
{
	$("header#mainHeader h1#idTag a").css('background-image', 'url('+nllogo+')');
}
else if( $(".lang-selected").text()=='en')
{
	$("header#mainHeader h1#idTag a").css('background-image', 'url('+enlogo+')');
}