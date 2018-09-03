<style>
    .pigeon-overlay {
        display: block;

        width: 1100px;
        height: 600px;

        position: absolute;
        top: 0;
        left: 0;

        opacity: .7;

        background: peachpuff;

        z-index: 99999999999999;
    }
</style>

<div class="pigeon-overlay">
    Hello Overlay

    <div style="float: right;">
        @template('logo')
    </div>
</div>
