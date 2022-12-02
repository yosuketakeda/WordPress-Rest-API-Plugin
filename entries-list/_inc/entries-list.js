jQuery( function( $ ) {

    $( document ).on( 'click', function( e ) {        
        last_num = parseInt( $( '.page-amount' ).val() );                              // last page number - total pages
        lists_per_page = parseInt( $( '.lists-per-page' ).val() );                     // count of lists in a page
        pageBtns_counts = parseInt( $( '.pageBtns-counts' ).val() );                   // count of shown pagination buttons
        
        flag = false;                                                                  // flag of Ajax call
        btn_event = '';                                                                // button event in pagination

        var curr_num;                                                               
        $( '#entries-list .page-nums' ).find( 'a' ).each( function() {                
            buf = $( this ).attr( 'class' );
            if ( buf.indexOf( 'active' ) > 0 ){
                curr_num = parseInt( buf.replace( 'active', '' ) );
            }
        } );           
        
        if ( e.target.className == 'prev-page' ) {                                       // case of clicking the pre-page button
            if ( curr_num > 1 ) {
                $( '.page-nums a.' + curr_num ).removeClass( 'active' );
                curr_num -= 1;                                    
                flag = true;  
                btn_event = 'prev';                                                
            }
        } else if ( e.target.className == 'next-page' ) {                                // case of clicking the next-page button
            if ( curr_num < last_num ) {                
                $( '.page-nums a.' + curr_num ).removeClass( 'active' );
                curr_num += 1;
                flag = true;
                btn_event = "next";
            }
        } else if ( e.target.className == 'go-firstpage' ) {                             // case of clicking the go-first page button
            curr_num = 1;
            flag = true;
            btn_event = 'first';
        } else if ( e.target.className == 'go-lastpage' ) {                              // case of clicking the go-last page button
            curr_num = last_num;
            flag = true;
            btn_event = 'last';
        } else {
            parent = $( e.target ).parent();
            
            if( parent.get( 0 ).className == 'page-nums' ) {                             // When clicked the page numbers button
                curr_num = e.target.className;
                if ( curr_num.indexOf( 'active' ) < 0 ) {
                    $( '#entries-list .page-nums' ).find( 'a' ).each( function() {
                        $( this ).removeClass( 'active' );
                    } );
                }
                flag = true;
                btn_event = 'number';
            }
            if ( parent.get( 0 ).className.indexOf( 'list-values' ) > 0 ) {               // When clicked item of list,  show the complete entry 

                list_id = ( ( parent.get( 0 ).className ).replace( 'list-values', '' ) ).replace( 'item-', '' );
                
                $.ajax( {
                    type:'POST',
                    url: pageObj.restURL + 'baseUrl/v1/baseEndPoint/list',
                    beforeSend: function( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', pageObj.restNonce );
                    },
                    data: { 
                        btn_event: 'item',
                        id: list_id
                    },
                    success: function( response ) {
                        if ( response ) {                            
                            $( '#entries-list .selected-item' ).remove();
                            obj = JSON.parse(response);
                            $( '#entries-list' ).append( '<div class="selected-item">' +
                                                            '<table>' +
                                                                '<tr>' +
                                                                    '<td class="item-name">User ID</td>' +
                                                                    '<td class="item-value">' + obj.id + '</td>' +
                                                                '</td>' +
                                                                '<tr>' +    
                                                                    '<td class="item-name">First Name</td>' +
                                                                    '<td class="item-value">' + obj.first_name + '</td>' +
                                                                '</td>' +
                                                                '<tr>' + 
                                                                    '<td class="item-name">Last Name</td>' +
                                                                    '<td class="item-value">' + obj.last_name + '</td>' +
                                                                '</td>' +
                                                                '<tr>' + 
                                                                    '<td class="item-name">Email</td>' +
                                                                    '<td class="item-value">' + obj.user_email + '</td>' +
                                                                '</td>' +
                                                                '<tr>' + 
                                                                    '<td class="item-name">Subject</td>' +
                                                                    '<td class="item-value">' + obj.subject + '</td>' +
                                                                '</td>' +
                                                                '<tr>' + 
                                                                    '<td class="item-name">Message</td>' +
                                                                    '<td class="item-value">' + obj.message + '</td>' +
                                                                '</td>' +
                                                                '<tr>' + 
                                                                    '<td class="item-name">Created Date</td>' +
                                                                    '<td class="item-value">' + obj.created_at + '</td>' +
                                                                '</tr>' + 
                                                            '</table>' +
                                                        '</div>' );
                        }
                    }
                });
            }
        }
        
        if ( flag ) {           // Run the pagination
            $.ajax( {
                type:'POST',
                url: pageObj.restURL + 'baseUrl/v1/baseEndPoint/list',
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', pageObj.restNonce );
                },
                data: { 
                    value: curr_num,
                    lists_per_page: lists_per_page
                },
                success: function( response ) {
                    if ( response ) {

                        // replacing the lists with new page number
                        $( '#entries-list .list-values' ).remove();                   // removing old page lists                        
                        obj = JSON.parse( response );

                        obj.forEach( element => {                                    
                            $( '.list-block' ).append( '<div class="'+ element.id + ' list-values">' +
                                                        '<div class="list-val">' + element.first_name + '</div>' +
                                                        '<div class="list-val">' + element.last_name + '</div>' +
                                                        '<div class="list-val">' + element.user_email + '</div>' +
                                                        '<div class="list-val">' + element.subject + '</div>' +
                                                        '</div>' );
                        });

                        // Updating pagination buttons
                        right_edge_num = parseInt( $( '.page-nums' ).children().eq( pageBtns_counts - 1 ).text() );
                        left_edge_num = parseInt( $( '.page-nums' ).children().eq( 0 ).text() );

                        if ( btn_event == 'next' ) {
                            if ( pageBtns_counts == 1 ) {
                                $( '.page-nums a' ).remove();
                                $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + curr_num + '">' + curr_num + '</a>' );
                            } else if ( curr_num == right_edge_num ) {
                                if (last_num - curr_num > 0) {
                                    $( '.page-nums a' ).remove();
                                    i = 0;
                                    while ( i < pageBtns_counts ) {
                                        i++;                            
                                        $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + ( left_edge_num + i ) + '">' + ( left_edge_num + i ) + '</a>' );
                                    }
                                }
                            }
                        }
                        if ( btn_event == 'prev' ) {
                            if ( pageBtns_counts == 1 ) {
                                $( '.page-nums a' ).remove();
                                $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + curr_num + '">' + curr_num + '</a>' );
                            } else if ( curr_num == left_edge_num ) {
                                if ( curr_num > 1 ) {
                                    $( '.page-nums a' ).remove();
                                    i = 0;
                                    while( i < pageBtns_counts ) {                                                                   
                                        $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + ( left_edge_num - 1 + i ) + '">' + ( left_edge_num - 1 + i ) + '</a>' );
                                        i++; 
                                    }
                                }
                            }
                        }
                        if ( btn_event == 'first' ) {
                            $( '.page-nums a' ).remove();
                            if ( last_num > pageBtns_counts ) {
                                bound = pageBtns_counts;
                            } else {
                                bound = last_num;
                            }
                            i = 0;
                            while( i < bound ) {
                                i++;                          
                                $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + i + '">' + i + '</a>' );
                            }                            
                        }
                        if ( btn_event == 'last' ) {
                            $( '.page-nums a' ).remove();
                            i = 0;
                            if ( last_num > pageBtns_counts ) {
                                while( i < pageBtns_counts ) {
                                    i++;
                                    $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + ( last_num - pageBtns_counts + i ) + '">' + ( last_num-pageBtns_counts + i ) + '</a>' );
                                }    
                            } else {
                                while ( i < last_num ) {
                                    i++;
                                    $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + i + '">' + i + '</a>' );
                                }    
                            }                                                    
                        }
                        if ( btn_event == 'number') {
                            if ( curr_num == right_edge_num ) {
                                if ( last_num - curr_num > 0 ) {
                                    $( '.page-nums a' ).remove();
                                    i = 0;
                                    while ( i < pageBtns_counts ) {
                                        i++;                            
                                        $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + ( left_edge_num + i ) + '">' + ( left_edge_num + i ) + '</a>');
                                    }
                                }
                            }
                            if ( curr_num == left_edge_num ) {
                                if ( curr_num > 1 ) {
                                    $( '.page-nums a' ).remove();
                                    i = 0;
                                    while ( i < pageBtns_counts ) {                                                                   
                                        $( '.page-nums' ).append( '<a href="javascript:void(0)" class="' + ( left_edge_num - 1 + i ) + '">' + ( left_edge_num - 1 + i ) + '</a>');
                                        i++; 
                                    }
                                }
                            }
                        }
                        $( '.page-nums a.' + curr_num ).addClass( 'active' );
                    }
                },
                error: function (error) {
                    alert( 'error -- ' + eval( error ) );
                }
            });

            $( '#entries-list .selected-item' ).remove();                             // removing the old completed entry

        }
    });

});