# Extensia Newsman pentru Magento 2 - Ghid de Configurare

Acest ghid prezinta toate setarile din extensia Newsman pentru Magento 2, pentru a va putea conecta magazinul la contul Newsman si a incepe sa colectati abonati, sa trimiteti newslettere si sa urmariti comportamentul clientilor.

---

## Unde Gasiti Setarile Extensiei

Dupa instalarea extensiei, accesati **Stores > Settings > Configuration** in panoul de administrare Magento. In bara laterala din stanga veti vedea un tab **Newsman** cu doua sectiuni:

- **General** - Conexiune API, sincronizare abonati, export date si setari dezvoltatori
- **Remarketing** - Urmarire vizitatori si pixel de remarketing

Toate setarile pot fi configurate per **Store View**, per **Website** sau ca **Default** pentru toate magazinele. Folositi selectorul de scope din partea de sus a paginii pentru a alege la ce nivel configurati.

---

## Primii Pasi - Conectarea la Newsman

Inainte de a putea folosi orice functionalitate, trebuie sa conectati extensia la contul dvs. Newsman. Exista doua modalitati:

### Optiunea A: Configurare Rapida cu OAuth (Recomandat)

1. Accesati **Stores > Configuration > Newsman > General**.
2. In sectiunea **About**, faceti click pe butonul **Configure with Newsman Login**.
3. Veti fi redirectionat catre site-ul Newsman. Autentificati-va daca este necesar si acordati acces.
4. Veti fi redirectionat inapoi catre o pagina in Magento unde alegeti lista de email dintr-un dropdown. Selectati lista pe care doriti sa o folositi si faceti click pe **Save**.
5. Asta e tot - API Key, User ID si Lista sunt toate configurate.

### Optiunea B: Configurare Manuala

1. Autentificati-va in contul Newsman pe newsman.app.
2. Accesati setarile contului si copiati **API Key** si **User ID**.
3. In Magento, accesati **Stores > Configuration > Newsman > General**.
4. Deschideti sectiunea **API (Credentials)**.
5. Inserati **User ID** si **API Key** in campurile corespunzatoare.
6. Faceti click pe **Test Credentials** pentru a verifica conexiunea. Daca este reusita, va aparea un mesaj de confirmare.
7. Faceti click pe **Synchronize Lists and Segments** pentru a incarca listele Newsman in dropdown.
8. Selectati **List ID** din dropdown si optional un **Segment ID**.
9. Faceti click pe **Save Config**.

---

## Reconfigurare cu Newsman OAuth

Daca trebuie sa reconectati extensia la un alt cont Newsman, sau daca credentialele s-au schimbat, accesati **Stores > Configuration > Newsman > General** si faceti click pe butonul **Configure with Newsman Login** din sectiunea About. Acest lucru va va ghida prin acelasi flux OAuth descris mai sus - veti fi redirectionat catre site-ul Newsman pentru a autoriza accesul, apoi inapoi in Magento pentru a selecta lista de email. API Key, User ID si Lista vor fi actualizate cu noile credentiale.

---

## Sectiunea General

Accesati **Stores > Configuration > Newsman > General** pentru a configura comportamentul de baza al extensiei.

### About

Aceasta sectiune afiseaza versiunea extensiei si ofera butonul **Configure with Newsman Login** pentru configurarea prin OAuth.

### Setari Generale

- **Active** - Activeaza sau dezactiveaza toate functiile Newsman. Cand este setat pe "No", extensia este complet inactiva. Activat implicit.

- **Send User IP Address** - Cand un vizitator se aboneaza, extensia poate trimite adresa IP a acestuia catre Newsman. Acest lucru poate ajuta la analiza si conformitate. Dezactivat implicit.

- **Server IP Address** - O adresa IP de rezerva folosita cand "Send User IP Address" este dezactivata. De obicei puteti lasa acest camp gol.

### API (Credentials)

- **User ID** - User ID-ul dvs. Newsman. Se completeaza automat daca ati folosit OAuth.

- **API Key** - API Key-ul dvs. Newsman. Aceasta valoare este stocata criptat. Se completeaza automat daca ati folosit OAuth.

- **Test Credentials** - Faceti click pe acest buton pentru a verifica ca User ID si API Key sunt corecte. Va aparea un mesaj de succes sau eroare.

- **API Timeout** - Cate secunde asteapta extensia un raspuns de la Newsman inainte de a renunta. Valoarea implicita de 60 de secunde functioneaza bine pentru majoritatea configuratiilor. Valoarea minima permisa este de 5 secunde.

- **Synchronize Lists and Segments** - Faceti click pe acest buton pentru a prelua toate listele si segmentele din Newsman. Trebuie sa faceti acest lucru inainte de a putea selecta o lista sau un segment mai jos.

- **List ID** - Selectati lista de email Newsman care va primi abonatii dvs. Trebuie sa faceti click mai intai pe "Synchronize Lists and Segments" pentru a popula acest dropdown.

- **Segment ID** - Optional, selectati un segment din lista aleasa. Segmentele va permit sa organizati abonatii in grupuri. Daca nu folositi segmente, lasati acest camp gol.

### Export

Aceste setari controleaza modul in care datele sunt partajate intre magazinul Magento si Newsman.

- **Authorization Header Name / Key** - Aceasta este o optiune veche (legacy) pentru protejarea exporturilor de date cu credentiale de securitate personalizate. Daca v-ati conectat prin OAuth, nu trebuie sa le setati - extensia gestioneaza autentificarea automat. Trebuie sa le completati doar daca ati configurat conexiunea manual si doriti sa adaugati un nivel suplimentar de securitate la exporturile de date.

- **Customer Attributes Map** - Un tabel dinamic unde puteti mapa atributele clientilor din Magento la campuri Newsman. De exemplu, puteti mapa "date_of_birth" la un camp personalizat in Newsman. Faceti click pe **Add** pentru a crea o noua linie de mapare. Aceasta este optional si necesara doar daca doriti sa trimiteti date suplimentare despre clienti catre Newsman, in afara campurilor standard.

- **Product Attributes Map** - Un tabel dinamic unde puteti mapa atributele produselor din Magento la campuri Newsman. De exemplu, puteti mapa "manufacturer" sau "color" la campuri personalizate in Newsman. Aceasta este optional si necesara doar daca doriti sa trimiteti date suplimentare despre produse.

- **Send customer telephone number** - Cand este activat, numerele de telefon ale clientilor din adresele de facturare/livrare sunt incluse in exporturile de date catre Newsman. Dezactivat implicit.

- **Send telephone number from order** - Cand este activat, numerele de telefon din adresele de facturare/livrare ale comenzilor sunt incluse in exporturile de date catre Newsman. Dezactivat implicit.

### Newsletter

- **Send Subscribe/Unsubscribe Emails From Newsman** - Cand este setat pe "Yes" (valoarea implicita), Newsman se ocupa de trimiterea emailurilor de confirmare a abonarii si dezabonarii in locul Magento. Aceasta va ofera mai mult control asupra designului emailurilor prin contul dvs. Newsman. Setati pe "No" daca doriti ca Magento sa trimita aceste emailuri folosind sabloanele sale incorporate.

### Setari pentru Dezvoltatori

Aceste setari sunt destinate utilizatorilor avansati si dezvoltatorilor. In cele mai multe cazuri, ar trebui sa le lasati la valorile implicite.

- **Logging Mode** - Controleaza cat de mult detaliu scrie extensia in fisierul de log. Valoarea implicita este **Error**, care inregistreaza doar problemele. Setati la **Debug** daca investigati o problema (dar nu uitati sa il setati inapoi dupa aceea, deoarece modul Debug creeaza fisiere de log mari). Setati la **None** pentru a dezactiva complet logarea. Niveluri disponibile: None, Error (implicit), Warning, Info, Debug.

- **Log Clean** - Sterge automat fisierele de log mai vechi decat acest numar de zile. Valoarea implicita este de 90 de zile.

- **Activate Test User IP / Test User IP address** - Doar pentru dezvoltare si testare. Va permite sa simulati o adresa IP specifica de vizitator. Lasati-le dezactivate in productie.

---

## Sectiunea Remarketing

Accesati **Stores > Configuration > Newsman > Remarketing** pentru a configura urmarirea vizitatorilor.

Remarketing-ul permite Newsman sa urmareasca ce pagini si produse vizualizeaza vizitatorii dvs., astfel incat sa le puteti trimite emailuri personalizate (de ex., reamintiri de cos abandonat, recomandari de produse).

### Setari Generale

- **Active** - Activeaza sau dezactiveaza pixelul de remarketing pe magazinul dvs. Activat implicit.

- **Newsman Remarketing ID** - Acesta identifica magazinul dvs. in sistemul de urmarire Newsman. Se completeaza automat daca ati folosit OAuth. Il puteti gasi si in contul Newsman la setarile de remarketing.

- **Use Proxy** - Cand este activat (valoarea implicita), toate cererile de urmarire sunt rutate prin serverul dvs. Magento in loc sa fie trimise direct din browserul vizitatorului catre Newsman. Aceasta imbunatateste confidentialitatea si poate ajuta cu blocantele de reclame (ad blockers). Cand este dezactivat, scripturile de urmarire sunt incarcate direct de pe serverele Newsman.

- **Anonymize IP Address** - Cand este activat, adresele IP ale vizitatorilor sunt anonimizate inainte de a fi trimise catre Newsman. Recomandat pentru conformitatea GDPR. Dezactivat implicit.

- **Brand Attribute** - Selectati ce atribut de produs Magento este folosit ca nume de brand in datele de remarketing. Valoarea implicita este "manufacturer". Daca magazinul dvs. foloseste un alt atribut pentru branduri, selectati-l aici.

- **Script** - Codul JavaScript de urmarire folosit de pixelul de remarketing. Acesta este generat automat si nu ar trebui editat manual decat daca sunteti instruit de suportul Newsman.

### Ce se Urmareste

Pixelul de remarketing urmareste automat activitatea vizitatorilor pe magazinul dvs.:

- **Pagini de produs** - Inregistreaza ce produse vizualizeaza vizitatorii, inclusiv informatii despre brand
- **Pagini de categorie** - Inregistreaza ce categorii navigheaza vizitatorii
- **Rezultate cautare** - Inregistreaza ce cauta vizitatorii
- **Cos de cumparaturi** - Inregistreaza continutul si valoarea cosului
- **Confirmare comanda** - Inregistreaza achizitiile finalizate cu valoarea si articolele comenzii

### Setari pentru Dezvoltatori

- **Log Proxy Requests** - Cand este activat, inregistreaza toate cererile HTTP proxy pentru depanare. Lasati dezactivat decat daca investigati probleme de remarketing.

---

## Intrebari Frecvente

### Cum stiu daca conexiunea functioneaza?

Accesati **Stores > Configuration > Newsman > General > API (Credentials)** si faceti click pe butonul **Test Credentials**. Daca User ID si API Key sunt corecte, veti vedea un mesaj de succes si listele dvs. vor fi afisate.

### Am facut click pe "Synchronize Lists and Segments" dar dropdown-ul este gol. Ce ar trebui sa fac?

Asigurati-va mai intai ca API Key si User ID sunt corecte facand click pe **Test Credentials**. Fiecare cont Newsman are cel putin o lista implicit, deci daca credentialele sunt corecte, listele vor aparea dupa sincronizare.

### Care este diferenta intre Customer Attributes Map si Product Attributes Map?

Acestea sunt tabele optionale de mapare care va permit sa trimiteti date suplimentare din Magento catre Newsman. Customer Attributes Map trimite campuri suplimentare din profilul clientului (precum data nasterii sau grupul de clienti), iar Product Attributes Map trimite campuri suplimentare ale produselor (precum culoarea sau producatorul). Aveti nevoie de acestea doar daca doriti sa folositi aceste date suplimentare in campaniile sau segmentele Newsman.

### Ce face "Use Proxy" in Remarketing?

Cand este activat, scripturile de urmarire si datele sunt rutate prin serverul dvs. Magento in loc sa fie incarcate direct de pe serverele Newsman de catre browserul vizitatorului. Aceasta inseamna ca blocantele de reclame sunt mai putin probabil sa blocheze urmarirea, iar browserele vizitatorilor nu fac conexiuni directe catre servere terte, ceea ce este mai bun pentru confidentialitate.

### Unde sunt logurile extensiei?

Extensia scrie loguri in propriile fisiere de log din directorul `var/log/` al Magento. Nivelul de logare este controlat din Setarile pentru Dezvoltatori. Fisierele de log mai vechi decat numarul de zile configurat (implicit: 90) sunt curatate automat saptamanal.

### Pot configura liste diferite pentru store view-uri diferite?

Da. Toate setarile suporta sistemul de scope al Magento. Folositi selectorul de **Store View** din partea de sus a paginii de configurare pentru a configura liste, segmente sau ID-uri de remarketing diferite pentru fiecare store view.

### Ce se intampla cand un client se aboneaza la newsletter?

Cand un client se aboneaza prin formularul de newsletter al Magento, extensia trimite automat abonarea catre Newsman folosind lista si segmentul configurate. Daca "Send Subscribe/Unsubscribe Emails From Newsman" este activat, Newsman va trimite emailul de confirmare in loc de Magento.
