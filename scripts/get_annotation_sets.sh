#!/bin/sh

echo "Retrieving Coding Sequence (CDS) annotation sets"
wget -nc \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode3b.interval \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode3b.fa \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode3c.interval \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode3c.fa \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode4.interval \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode4.fa \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode5.interval \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode5.fa \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode6.interval \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode6.fa \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode7.interval \
     http://homes.gersteinlab.org/people/lh372/VAT/gencode7.fa

#echo "Retrieving miRNA annotation sets"
#wget -nc \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode3b.miRNA.interval \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode3b.miRNA.fa \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode3c.miRNA.interval \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode3c.miRNA.fa \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode4.miRNA.interval \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode4.miRNA.fa \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode5.miRNA.interval \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode5.miRNA.fa \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode6.miRNA.interval \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode6.miRNA.fa \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode7.miRNA.interval \
#     http://homes.gersteinlab.org/people/lh372/VAT/gencode7.miRNA.fa
