const accessConzentCookie = (name) => {
	const conzent_cookies = document.cookie.split(";").reduce((acc, cookieString) => {
		const [key, value] = cookieString.split("=").map((s) => s.trim());
		if (key && value) {
			acc[key] = decodeURIComponent(value);
		}
		return acc;
	}, {});
	return name ? conzent_cookies[name] || false : conzent_cookies;
};
window.addEventListener("load", function() {
	var conzent_id = accessConzentCookie('conzent_id');
	if(document.querySelector("#conzentId")){
		if(conzent_id){
			document.querySelector("#conzentId").innerHTML = conzent_id;
		}
		else{
			document.querySelector("#conzentId").innerHTML = "Not found";
		}
	}
	
});