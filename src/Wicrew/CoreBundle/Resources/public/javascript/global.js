(function($) {

    /**
     * Menu
     */
     $('.hamburger-icon').on('click', function(e) {
        e.preventDefault();
        $(this).toggleClass('open'); 
        $('#megamenu-mobile').toggleClass('open'); 
    });
    $('button.main-menu').on('mouseenter', function(e) { 
        e.preventDefault();
        $(this).addClass('open'); 
        let megaMenuClass = $(this).data('megaMenuClass');
        $('.megamenu.'+megaMenuClass).addClass('open');
        $('.megamenu').each(function() {
            if ($(this).attr('id') != $('.megamenu.'+megaMenuClass).attr('id') ) {
                $(this).removeClass('open');
                let menuLabelClassOtherMenu = $(this).data('menuLabelClass');
                if (menuLabelClassOtherMenu) {
                    $('.'+menuLabelClassOtherMenu).removeClass('open');
                }
            }
        }); 
    });

    $('.mobile-menu').on('click', function(){
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            $('.navigation-wrapper').removeClass('active');
            $('.header').removeClass('mobile-menu-active');
            $('.menu-overlay').remove();
        } else {
            $(this).addClass('active');
            $('.navigation-wrapper').addClass('active');
            $('.header').addClass('mobile-menu-active');
            $('body').append('<div class="menu-overlay"></div>');
            
            $('.menu-overlay').on('click', function(){
                $('.mobile-menu, .navigation-wrapper').removeClass('active');
                $('.header').removeClass('mobile-menu-active');
                $('.menu-overlay').remove();
                return false;
            });
        }
        return false;
    });

    $('.main-navigation .level0').on('mouseenter', function(e){
        if (window.innerWidth > 768) {
            e.preventDefault();
            $('.header').removeClass('menu-open');
            $('.main-navigation .level0').removeClass('open');
            $('.submenu').stop().fadeOut();

            if ($(this).hasClass('menu-haschild')) {
                $(this).addClass('open');
                $('.header').addClass('menu-open');
                $(this).find('.submenu').stop().fadeIn();
            }
        }
    });

    $('.main-navigation .level0.menu-haschild .submenu').on('mouseleave', function(e){
        if (window.innerWidth > 768) {
            e.preventDefault();
            $(this).parents('.menu-haschild').removeClass('open');
            $('.header').removeClass('menu-open');
            $(this).stop().fadeOut();
        }
    });

    $('.main-navigation .level0.menu-haschild > a').on('click', function(){
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            $(this).next().slideUp();
        } else {
            $(this).addClass('active');
            $(this).next().slideDown();
        }
        return false;
    });

    /**
     * Sticky menu
     */
    $(window).on('scroll', function(){
        var scroll = $(window).scrollTop();

        if (scroll > 100) {
            $('.header').addClass("sticky");
        } else {
            $('.header').removeClass("sticky");
        }
    });
    
    $(document).mousemove(function(){ 
       if($(".main-menu-element:hover").length != 0){ 
       } else{ 
           $('.megamenu-desktop').removeClass('open');
           $('button.main-menu').removeClass('open');
       }
   });
     
    /**
     * Testimonials Slider
     */
    $('.testimonials-slider').slick({
        slidesToShow: 3,
        dots: true,
        arrows: false,
        centerMode: true,
        centerPadding: '0px',
        responsive: [
        {
            breakpoint: 768,
            settings: {
                slidesToShow: 1,
            }
        }
    ]
    });


    /**
     * Activity Filter on mobile
     */
    $('.filter-title').on('click', function(e) {
        if ( $(window).width() < 768 ) {
            e.preventDefault();
            $(this).toggleClass('opened');
            $('.filter-items').slideToggle('fast');
        }
    });

    $('.filter-checkbox input[type=checkbox]').on('click', function() {

        window.location.href = $(this).attr('data-link');

    });

    /**
     * Tabs
     */
    $('.tabs a.tab').on('click', function(e) {
        e.preventDefault();

        var target = $(this).data('target');
        $(this).closest('.tabs-group').find('a.tab').removeClass('active');
        $(this).addClass('active')
        $(this).closest('.tabs-group').find('.tab-content').removeClass('active').hide();

        if ( $(target).length ) {
            $(target).fadeIn(400).addClass('active');
        }
    });

    /*================================
    =            Activity            =
    ================================*/

    $('.activity-tab-desc-block a').click(function(e) {
        e.preventDefault();

        var obj = $(this);
        var target = obj.attr('data-target');

        $([document.documentElement, document.body]).animate({
            scrollTop: $( target ).offset().top - 90
        }, 800);
    });

    $(".activity-slider-block").slick();

    /*=====  End of Activity  ======*/

    /*=======================================
    =           Read Only Fields            =
    =======================================*/

    // Readonly fields can't be marked as required in a form, which is needed for date inputs.
    // So make our own readonly definition.
    $(".readonly").on("keydown paste", function(e) {
        e.preventDefault();
    });

    /*=====  End of Time readonly  ======*/



    /*======================================
    =            Floating label            =
    ======================================*/

    $(document).on('focus click', '.floating-label-wrap > input, .floating-label-wrap > textarea',  function(e) {
        var obj = $(this);

        obj.addClass('focus');
    });

    $(document).on('blur', '.floating-label-wrap > input, .floating-label-wrap > textarea',  function(e) {
        var obj = $(this);

        obj.removeClass('focus');
    });

    $(document).on('input change keyup focus blur mousedown', '.floating-label-wrap > input, .floating-label-wrap > textarea',  function(e) {
        var obj = $(this);
        var val = obj.val();

        if ( val ) {
            obj.addClass('has-val');
        } else {
            obj.removeClass('has-val');
            obj.parents('.floating-label-wrap').removeClass('float-fixed');
        }
    });

    $(".floating-label-wrap > input, .floating-label-wrap > textarea").each(function () {
        let obj = $(this);
        let val = obj.val();

        if (val) {
            obj.addClass("has-val");
        } else {
            obj.removeClass("has-val");
            obj.parents(".floating-label-wrap").removeClass("float-fixed");
        }
    });

    /*=====  End of Floating label  ======*/


})(jQuery);
