@extends(Auth()->user()->type==cons('user.type.retailer') ? 'index.retailer-master' : 'index.manage-left' )