<div class="container">
    <div class="grid">
        <div class="grid-lg-3 grid-md-2 grid-sm-12 hidden-xs hidden-sm no-margin no-padding"></div>
        <div class="grid-lg-6 grid-md-8 grid-sm-12">
           <span class="h1">Du måste logga in för att se den här sidan</span>

            @if (isset($_GET['login']) && $_GET['login'] === 'failed')
                <div class="notice info margin-top">
                     <i class="pricon pricon-notice-warning"></i> Det användarnamn eller lösenord som du angav är felaktigt.
                </div>
            @endif

        </div>
    </div>
</div>

<div class="container main-container">
    <div class="grid no-margin no-padding">
        <div class="grid-lg-3 grid-md-2 grid-sm-12 hidden-xs hidden-sm no-margin no-padding"></div>
        <div class="grid-lg-6 grid-md-8 grid-sm-12">
            @include('partials.user.loginform')
        </div>
    </div>
</div>
