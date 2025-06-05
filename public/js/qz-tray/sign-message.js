qz.security.setCertificatePromise(function(resolve, reject) {
    console.log('Solicitando certificado digital...');
    fetch('/certificates-qztray/digital')
    .then(response => response.text())
    .then(digital => { 
        console.log('Certificado recibido:', digital ? 'OK' : 'Usando certificado por defecto');
        let digitalResponse = digital;
        if (digitalResponse === '') {
            console.log('Usando certificado digital por defecto');
            resolve(digitalKey);
        } else {
            console.log('Usando certificado digital del servidor');
            resolve(digital);
        }
    })
    .catch(error => {
        console.error('Error obteniendo certificado:', error);
        reject(error);
    });
});

let privateKey = "-----BEGIN PRIVATE KEY-----\n" +
            "MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDCZwdmAMTY9qR6\n"+
            "NGtexKWs/jfbZyfc0U3LcYqo29jeyxzRCD06b+MJkTX8m4SRCstL2qvueOHiZvXo\n"+
            "JSVpRINCKuTkKzcMWi9sijiBIuzcl2m1ovAhX5d61DIfhBrvIcLp5mvK5YR+ygye\n"+
            "VWeDRqZ2j2kOcBJ7MLdx9yXlwMkUDa2x3uxyQfmHxv1RbYSxTRIYd+TQ8vHO6dFj\n"+
            "f6VaeQUbKSTlfUfI6012wUgvLNUfBJuJ67kIT+wjnHZwIkLwziLHtx+/CoXzDz4x\n"+
            "hRhCDqI9S5y43p8NAcYw0MVFa1WzRlMdmdH772PWO8uC9gnmPJhbVml07RG5ykAU\n"+
            "pxrWwkVxAgMBAAECggEAXU+YxIQ/+ChDAIlitCVNpMCNTRmxj5NDdRB1zuFfsmjp\n"+
            "1wfOY9tKrc/uiuaW9gupUyqN9jQ9sC9df2U9FM8W9c6i+UYo8RvkwYOC5bE+4g8n\n"+
            "ZVDlVA+PJRzvRiNhzkB1T1ITkVsjgrw23FUAD4n84tGpSo3OwSS8GM7ZePNVUPL9\n"+
            "PqxqKIyiVp4enWUis6wn0bm4DAUu7oNqcMrjaToHhlUi0UMYqgFwOHQ3MPU0wdhy\n"+
            "+ncyN4MhIeX1LlirlmVP8AvYKwKKgfRb84y1jPTHfO+dNhMEUN5r9QfNAkwuazJv\n"+
            "cmlYdNsxjuW6fgsTZm3d0qASZiV+H01AJQo1oXuyAQKBgQDt7lGpk/O9H0Bt4S7K\n"+
            "ap7psYmMez9G6BVXuxaknNHY9XsKvwR/ZeIuvCcuyZ3lUqgdgEInbGNCmpVUTTS3\n"+
            "tgfKD25ZTofGxOI+koBx+0EFciARKc+xaNqXaUnJ1nugA2jhWLvwVrmv5FF2VhhW\n"+
            "OVljX7BqI+f1eov+6kHu3qxR0QKBgQDRKnX0f7Oz35tLVa8v6e5+W4qdVOcE88Ad\n"+
            "NRlODUGaWa1YFgiXc6Dcb9eFMUaoD4HMfVoirEd6ZjIzEb/l2MKqWaPYyLB4dNDF\n"+
            "ML3a/V7XXaWVMmVom9aLNUu8jKvhikKUUxkN+M6fWMMi8M92+B0oAcLQajVPYV8O\n"+
            "Ohc4OkEBoQKBgGwM6GT8XZorURUVSCyAUv6Js49qgQfwaZDX06aZ2OqQQHpW2PIK\n"+
            "ELdslta2lNAJw3LyRhilLkaW8O3BygkLz2nBrDk+Yoav7pa/7TjWA2c3trxUoo9M\n"+
            "sMhF9k6E6st2APElXOP+XoE0TJJS8uZlUOTCFdl9yN8/8ceoFp0l3lehAoGAchhs\n"+
            "UVubheHSjyyFLGi53JlIqnvWrL/dqtD9JbNbdru2L9eNBjhfpf8oHBJ+DUywLACw\n"+
            "uzsonl7CwVLMT6+GuG+/TZBjmsF15CqrVZpiMq51lUXxRTfEtxjyYD6Hv7awjMIr\n"+
            "Z5Cx/P/pKdUcBjRfiyQyxYc53zwpItSTN+um7CECgYANQJI4KOua/F48IDk+32Fg\n"+
            "URLSnZjRDh+LBWWj8EivEum4Q/J1kLHljnG77BQO9gUdXe6tqdfWrAAxABqAvHdD\n"+
            "GLQvKqg0GDz5IVyR32wX7dY35guqzEcorKyNHHwPrcheKTv3IR/Fp/tQjUpXGaR/\n"+
            "0bOVUb468IFv2kDlkY3DOA==\n"+
            "-----END PRIVATE KEY-----\n";


    qz.security.setSignaturePromise(function(toSign) {
        return function(resolve, reject) {
            // console.log('Firmando mensaje:', toSign);
            fetch("/certificates-qztray/private")
            .then(response => response.text())
            .then(private => {
                console.log('Llave privada recibida:', private ? 'OK' : 'Usando llave por defecto');
                let key = private || privateKey;
                try {
                    var pk = KEYUTIL.getKey(key);
                    var sig = new KJUR.crypto.Signature({"alg": "SHA1withRSA"});
                    sig.init(pk);
                    sig.updateString(toSign);
                    var hex = sig.sign();
                    console.log('Firma generada exitosamente');
                    resolve(stob64(hextorstr(hex)));
                } catch (err) {
                    console.error('Error en firma:', err);
                    reject(err);
                }
            })
            .catch(error => {
                console.error('Error obteniendo llave privada:', error);
                reject(error);
            });
        };
    });
