<?php
    return [
        "languages" => ["pl"],
        //"currencies" => ["PLN", "EUR", "USD"],
        "currencies" => ["PLN"],
        "vat" => [
            "23" => "23%",
            "22" => "22%",
            "8" => "8%",
            "7" => "7%",
            "5" => "5%",
            "3" => "3%",
            "0" => "0%",
            "zw." => "zw.",
            "np." => "np.",
            "o.o." => "o.o.",
        ],
        "gtu" => [
			"GTU_01" => "Dostawa napojów alkoholowych",
			"GTU_02" => "Dostawa paliw silnikowych",
			"GTU_03" => "Dostawa oleju opałowego, olejów smarownych oraz smarów plastycznych itp.",
			"GTU_04" => "Dostawa wyrobów tytoniowych oraz wyborów nowatorskich",
			"GTU_05" => "Dostawa odpadów",
			"GTU_06" => "Dostawa urządzeń elektronicznych oraz części i materiałów do nich",
			"GTU_07" => "Dostawa pojazdów oraz części samochodowych",
			"GTU_08" => "Dostawa metali szlachetnych oraz nieszlachetnych",
			"GTU_09" => "Dostawa leków oraz wyrobów medycznych",
			"GTU_10" => "Dostawa budynków, budowli i gruntów.",
			"GTU_11" => "Świadczenie usług w zakresie przenoszenia uprawnień do emisji gazów cieplarnianych",
			"GTU_12" => "Świadczenie usług doradczych, księgowych, prawnych, zarządczych, szkoleniowych, marketingowych",
			"GTU_13" => "Świadczenie usług transportowych i gospodarki magazynowej",
		],
		"sale_document_types" => [
			"invoice" => "Faktura VAT",
			"proforma" => "Proforma",
		],
        "default_mask" => [
            "invoice" => "@N5/@Y/@M",
            "proforma" => "@N5/@Y/@M",
        ],
        "default_continuation" => [
            "invoice" => "year",
            "proforma" => "month",
        ]
    ];