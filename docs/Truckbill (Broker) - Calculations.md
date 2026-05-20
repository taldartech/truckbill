# Fields of LR \- 

1. weight  
2. unloading\_weight  
3. fr\_rate  
4. is\_fixed\_amount  
5. amount  
6. actual\_amount  
7. damage\_amount  
8. shortage\_amount

# Field of FR \-

1. tr\_fr\_rate  
2. is\_fixed\_amount  
3. tr\_amount  
4. advance\_amount  
5. balance\_amount   
6. tds\_amount  
7. detention  
8. deviation misc\_expense  
9. damage\_amount  
10. shortage\_amount

## 

# LR Calculations

## Create LR

**weight \= unloading\_weight (user input)**  
**damage\_amount \= 0**  
**shortage\_amount \= 0**

If using fixed amount  
Then **amount \= actual\_amount \= user input**  
If using calculation  
Then **amount \= actual\_amount \= fr\_rate \* weight**

## Update LR

**weight \= (user input)**

If using fixed amount  
Then **actual\_amount \= user input**  
If using calculation  
Then **actual\_amount \= fr\_rate \* min(weight, unloading\_weight)**

**Amount \= actual\_amount \- shortage\_amount \- damage\_amount**

#### FR

If weight changes \-   
If using a fixed amount then no update.  
If using calculation, then \-   
**tr\_amount \= tr\_fr\_rate \* min(weight, unloading\_weight)**  
If **canChangeTdsAmount,** then **tds\_amount \= tr\_fr\_rate \* weight \* tds\_percentage / 100**

**balance\_amount \= tr\_amount \- shortage\_amount \- damage\_amount \- misc\_expense \+ detention \+ deviation \- tds\_amount \- advance\_amount \+ negative\_adjusted\_amount**

**If balance\_amount \< 0**  
**negative\_adjusted\_amount \= negative\_adjusted\_amount \- balance\_amount**  
**transporter-\>advance\_amount \-= balance\_amount**  
**Create an entry for advance payment with negative adjustment remarks.**

# FR Calculations

## Create FR

**advance\_amount \= 0**  
**detention \= 0**  
**deviation \= 0**  
**misc\_expense \= 0**  
**damage\_amount \= 0**  
**shortage\_amount \= 0**  
**negative\_adjusted\_amount \= 0**

If using fixed amount  
Then **tr\_amount \= user input**  
If using calculation  
Then **tr\_amount \= tr\_fr\_rate \* weight**

**tds\_amount \= tr\_amount \* tds\_percentage / 100**  
**balance\_amount \= tr\_amount \- tds\_amount**

## Update FR

**tr\_fr\_rate \= user input**  
**unloading\_weight \= user input**  
**detention \= user input**  
**deviation \= user input**  
**misc\_expense \= user input**  
**damage\_amount \= user input**  
**shortage\_amount \= user input**  
**negative\_adjusted\_amount \= read only.**

If using fixed amount, then   
**tr\_amount \= user input**  
If **canChangeTdsAmount,** then **tds\_amount \= tr\_amount \* tds\_percentage / 100**

If using calculation  
Then **tr\_amount \= tr\_fr\_rate \* min(weight, unloading\_weight)**  
If **canChangeTdsAmount,** then **tds\_amount \= tr\_fr\_rate \* weight \* tds\_percentage / 100**

**advance\_amount \= sum of all voucher payment**

**balance\_amount \= tr\_amount \- shortage\_amount \- damage\_amount \- misc\_expense \+ detention \+ deviation \- tds\_amount \- advance\_amount \+ negative\_adjusted\_amount**

**If balance\_amount \< 0**  
**negative\_adjusted\_amount \= negative\_adjusted\_amount \- balance\_amount**  
**transporter-\>advance\_amount \-= balance\_amount**  
**Create an entry for advance payment with negative adjustment remarks.**

#### LR

If using calculation  
Then **actual\_amount \= fr\_rate \* min(weight, unloading\_weight)**  
**Amount \= actual\_amount \- shortage\_amount \- damage\_amount**

# Voucher Payment Calculation

## Create Voucher Payment

Amount, payment mode

Create an entry with received data.

#### Update FR

**advance\_amount \= sum of all previous voucher payment \+ amount**  
**balance\_amount \= tr\_amount \- shortage\_amount \- damage\_amount \- misc\_expense \+ detention \+ deviation \- tds\_amount \- advance\_amount**  
**OR**  
**advance\_amount \+= amount**  
**balance\_amount  \-= amount** 

If payment mode is advance  
**transpoter-\>advance\_amount \-= amount**  
**Create record in advance entry**

## Update Voucher Payment

Amount, payment mode

Update the entry with received data.

#### Update FR

**advance\_amount \= sum of all other voucher payment \+ amount**  
**balance\_amount \= tr\_amount \- shortage\_amount \- damage\_amount \- misc\_expense \+ detention \+ deviation \- tds\_amount \- advance\_amount**  
**OR**  
**advance\_amount \= advance\_amount \- previous amount \+ amount**  
**balance\_amount  \= balance\_amount  \+ previous amount \- amount** 

If **previous payment mode is advance**  
**transpoter-\>advance\_amount \+= previous amount**  
**Create revert record in advance entry**

If **Payment mode is advance**  
**transpoter-\>advance\_amount \-= amount**  
**Create record in advance entry**

**Negative adjustment —**  
