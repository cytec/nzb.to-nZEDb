<?xml version="1.0" encoding="UTF-8" ?>
<caps>
	<server appversion="0.0.1" version="0.1" title="nzb.to" strapline="Ein privater NZB Indexer" email="" url="http://nzb.to/" image="http://nzb.to/templates/Default/images/logo.png" />
	<limits max="100" default="100"/>

	<registration available="no" open="no" />

	<searching>
		<search available="yes"/>
		<tv-search available="yes"/>
		<movie-search available="yes"/>
		<audio-search available="no"/>
	</searching>

	<categories>
	<category id="2000" name="Filme">
		<subcat id="2045" name="UHD-Filme"/>
        <subcat id="2050" name="3D"/>
        <subcat id="2060" name="BluRay"/>
		<subcat id="2070" name="X265"/>
	</category>
	<category id="5000" name="TV">
		<subcat id="5030" name="Series"/>
        <subcat id="5030" name="UHD-Series"/>
		<subcat id="5080" name="Dokus"/>
	</category>
	</categories>
</caps>
