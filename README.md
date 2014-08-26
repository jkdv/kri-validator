KRI Validator
=============

논문 통합 검증 웹 어플리케이션

KRI Validator verifies thesis lists whether they are in KCI, SCI/SCIE, or others such as Scopus. The program sends a HTTP GET request message to the Korean Researcher Information system (KRI), and KRI responds to it with an XML message. KRI Validator parses the XML message and stores it into the MySQL database.
