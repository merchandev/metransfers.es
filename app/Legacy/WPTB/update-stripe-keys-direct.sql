-- ========================================
-- CONFIGURACIÓN STRIPE - USAR SOLO LOS TOKENS
-- NO usar los IDs (mk_...)
-- ========================================

-- PASO 1: Actualizar TOKEN público (Publishable Key)
-- TOKEN (CORRECTO): pk_live_51LznPe...
UPDATE wp_options 
SET option_value = 'pk_live_51LznPeH1P4XrTy9Cm8sxWqRMeRIAlUBqWAaF3FFafArxlS27tVrUrlB6l6HeacCaGTK1YqnNA6gAoMSljFjJPwFk00CzcbBfpd' 
WHERE option_name = 'wptb_stripe_publishable_key';

-- PASO 2: Actualizar TOKEN secreto (Secret Key)
-- TOKEN (CORRECTO): sk_live_51LznPe...
UPDATE wp_options 
SET option_value = 'sk_live_YOUR_LIVE_SECRET_KEY' 
WHERE option_name = 'wptb_stripe_secret_key';

-- PASO 3: Verificar que se guardaron los TOKENS correctos
-- Debe mostrar pk_live_... y sk_live_... (NO mk_...)
SELECT 
    option_name, 
    LEFT(option_value, 30) as token_preview,
    CASE 
        WHEN option_value LIKE 'pk_live_%' THEN '✅ Correcto'
        WHEN option_value LIKE 'sk_live_%' THEN '✅ Correcto'
        WHEN option_value LIKE 'mk_%' THEN '❌ ERROR - Usando ID en vez de TOKEN'
        ELSE '⚠️ Verificar formato'
    END as status
FROM wp_options 
WHERE option_name IN ('wptb_stripe_publishable_key', 'wptb_stripe_secret_key');

-- Resultado esperado:
-- wptb_stripe_publishable_key | pk_live_51LznPeH1P4XrTy9Cm8... | ✅ Correcto
-- wptb_stripe_secret_key      | sk_live_51LznPeH1P4XrTy9CoC... | ✅ Correcto
