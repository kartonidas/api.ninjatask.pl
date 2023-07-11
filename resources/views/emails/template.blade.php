<html>
	<body>
		<div style="width: 800px; margin: 0 auto; background-color: #FFFFFF ">
			<div style="margin-bottom: 20px">
                <img src="{{ $message->embed(resource_path() . "/mail-logo.png") }}">
			</div>
			<div style="font-size:14px;margin:0;padding:0;font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;line-height:1.5;height:100%!important;width:100%!important">
				<div style="border: 1px solid #f0f0f0; padding: 25px; margin:0;margin-bottom:30px;color:#294661;font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;font-size:16px;font-weight:300">
					<h2 style="margin:0;margin-bottom:30px;font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;font-weight:300;line-height:1.5;font-size:20px;color:#294661!important">
						@yield("title")
					</h2>
                    @yield("content")
				</div>
				<div style="font-size:10px; line-height: 11px; color: #444444; font-family:'Open Sans','Helvetica Neue','Helvetica',Helvetica,Arial,sans-serif;">
                    @if(app()->getLocale() == "en")
                        Please be advised that the administrator of personal data is Artur Patura running a business under
                        company Artur Patura - netextend.pl at the address of ul. Lodz 46 lok. 48, 97-300 Piotrków Trybunalski, NIP:
                        7712671332, REGON: 101425394. Personal data is processed in order to provide services and for
                        contact. Personal data will be stored for a period not longer than the limitation period in accordance with
                        with the provisions of the Civil Code. Each person whose personal data is processed by the administrator has
                        the right to access your data, the right to rectify it, delete it ("the right to be forgotten"),
                        processing restrictions, the right to transfer data, the right to object and the right to withdraw consent
                        data processing at any time. Detailed information on data processing
                        personal data can be found in our GDPR information clause available at
                        <a href="{{ env("SITE_URL") }}en/privacy-policy">
                            {{ env("SITE_URL") }}en/privacy-policy
                        </a>
                    @else
                        Informujemy, iż administratorem danych osobowych jest Artur Patura wykonujący działalność gospodarczą pod
                        firmą Artur Patura – netextend.pl pod adresem ul. Łódzka 46 lok. 48, 97-300 Piotrków Trybunalski, NIP:
                        7712671332, REGON: 101425394. Dane osobowe przetwarzane są w celu świadczenia usług oraz w celach
                        kontaktowych. Dane osobowe będą przechowywane przez czas nie dłuższy niż termin przedawnienia zgodnie
                        z przepisami Kodeksu cywilnego. Każda osoba, której dane osobowe są przetwarzane przez administratora ma
                        prawo dostępu do treści swoich danych, prawo do ich sprostowania, usunięcia („prawo do bycia zapomnianym”),
                        ograniczenia przetwarzania, prawo do przenoszenia danych, prawo sprzeciwu oraz prawo do cofnięcia zgody na
                        przetwarzanie danych w dowolnym momencie. Szczegółowe informacje na temat przetwarzania danych
                        osobowych znajdują się w naszej w klauzuli informacyjnej RODO dostępnej pod adresem
                        <a href="{{ env("SITE_URL") }}polityka-prywatnosci">
                            {{ env("SITE_URL") }}polityka-prywatnosci
                        </a>
                    @endif
				</div>
			</div>
		</div>	
	</body>
</html>