/**
 * Sociality Share WeChat
 */
const {
    jQuery: $,
    QRCode,
} = window;

const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent );
const shareURL = $( 'body' ).attr( 'data-share-url' );

if ( isMobile ) {
    $( '.sociality-share-wechat-copy-url' )
        .val( shareURL )
        .on( 'focus', function() {
            $( this ).select();

            try {
                const successful = document.execCommand('copy');

                if ( successful ) {
                    $( '.sociality-share-wechat-copied' ).css( 'visibility', 'visible' );
                }
            } catch ( e ) {}
        } );
} else {
    /**
     * Prepare QR code.
     */
    new QRCode( $( '.sociality-share-wechat-qrcode' )[ 0 ], {
        text: shareURL,
        width: 300,
        height: 300,
        colorDark : "#0aab58",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    } );
}

$( `.sociality-share-wechat-${ isMobile ? 'mobile' : 'desktop' }` ).show();
