public function buscar($asunto, $ficheroBusqueda) {
        $emails = imap_search ( $this->conexion, "ALL" );
        if ($emails) {
            /* Poner los emails más nuevos arriba */
            rsort ( $emails );
            
            foreach ( $emails as $email ) {
                $propiedades = imap_fetch_overview ( $this->conexion, $email );
                if (strpos ( $propiedades [0]->subject, $asunto )) {
                    
                    $structure = imap_fetchstructure ( $this->conexion, $email );
                    
                    // ompruebo si hay ficheros adjuntos
                    $attachments = array ();
                    if (isset ( $structure->parts ) && count ( $structure->parts )) {
                        for($i = 0; $i < count ( $structure->parts ); $i ++) {
                            $attachments [$i] = array (
                                    'is_attachment' => false,
                                    'filename' => '',
                                    'name' => '',
                                    'attachment' => '' 
                            );
                            if ($structure->parts [$i]->ifdparameters) {
                                foreach ( $structure->parts [$i]->dparameters as $object ) {
                                    if (strtolower ( $object->attribute ) == 'filename') {
                                        $attachments [$i] ['is_attachment'] = true;
                                        $attachments [$i] ['filename'] = $object->value;
                                    }
                                }
                            }
                            if ($structure->parts [$i]->ifparameters) {
                                foreach ( $structure->parts [$i]->parameters as $object ) {
                                    if (strtolower ( $object->attribute ) == 'name') {
                                        $attachments [$i] ['is_attachment'] = true;
                                        $attachments [$i] ['name'] = $object->value;
                                    }
                                }
                            }
                            if ($attachments [$i] ['is_attachment']) {
                                $attachments [$i] ['attachment'] = imap_fetchbody ( $this->conexion, $email, $i + 1 );
                                if ($structure->parts [$i]->encoding == 3) { // 3 = BASE64
                                    $attachments [$i] ['attachment'] = base64_decode ( $attachments [$i] ['attachment'] );
                                } elseif ($structure->parts [$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                                    $attachments [$i] ['attachment'] = quoted_printable_decode ( $attachments [$i] ['attachment'] );
                                }
                            }
                        }
                    }
                    
                    foreach ( $attachments as $adjunto ) {
                        if ($adjunto ['is_attachment']) {
                            $nombreFichero = $adjunto ['filename'];
                            $adjunto = $adjunto ['attachment'];
                            echo "Comparando $ficheroBusqueda con $nombreFichero<br />";
                            if ($ficheroBusqueda == $nombreFichero) {
                                if ($adjunto) {
                                    $gestor = fopen ( $nombreFichero, 'w' );
                                    fwrite ( $gestor, $adjunto );
                                    fclose ( $gestor );
                                    return $nombreFichero;
                                }
                            } // Fin de nombre fichero
                        } // Fin de es adjunto
                    } // Fin de foreach adjunto                 
                } // Fin de si coincide el asunto
            } // Fin de foreach email
        } // Fin de si hay emails
        return false; // Sólo sino encontró ficheros
    } // Fin de buscar correos
