jQuery(document).ready(function($){
    var table = jQuery('#tRight').DataTable({
        pagingType : "full",
        lengthChange : false,
        language: {
            "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/German.json",
        }
    });
    var table2 = jQuery('#tBot').DataTable({
        pagingType : "full",
        lengthChange : false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/German.json"
        }
    });
});

function goToPopup(popup){
    if(myMap.hasLayer(popup)){
        myMap.flyTo([popup.getLatLng().lat + .01,popup.getLatLng().lng],13);
        (function smoothscroll(){
            var currentScroll = document.documentElement.scrollTop || document.body.scrollTop;
            if (currentScroll > 0) {
                window.requestAnimationFrame(smoothscroll);
                window.scrollTo (0,currentScroll - (currentScroll/10));
            }
        })();
        popup.openPopup();
    }
}
