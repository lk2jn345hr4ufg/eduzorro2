<?php

/*
|--------------------------------------------------------------------------
| Math tools
|--------------------------------------------------------------------------
| Each entry drives a calculator rendered by resources/views/tools/partials/math.blade.php.
|
|   fields  — inputs. type: number (default) | text | select
|             label = a key inside the math.php language files
|   outputs — result rows, same label lookup
|   js      — body of a JS function receiving the field ids as arguments and
|             returning an object keyed by output id. Numbers are passed as
|             Number, text/select as String.
|
| Adding a tool = one entry here + one row in the admin (same slug).
*/

return [

    // ---------------------------------------------------------------- basics

    'percentage-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'value', 'default' => 250],
            ['id' => 'b', 'label' => 'percent', 'default' => 20],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'percent_of'],
            ['id' => 's', 'label' => 'what_percent'],
            ['id' => 'p', 'label' => 'plus_percent'],
            ['id' => 'm', 'label' => 'minus_percent'],
        ],
        'js' => 'return {r: a*b/100, s: b ? (a/b*100) : null, p: a + a*b/100, m: a - a*b/100};',
    ],

    'percentage-change-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'old_value', 'default' => 120],
            ['id' => 'b', 'label' => 'new_value', 'default' => 150],
        ],
        'outputs' => [
            ['id' => 'c', 'label' => 'change_percent'],
            ['id' => 'd', 'label' => 'difference'],
        ],
        'js' => 'return {c: a ? ((b-a)/Math.abs(a)*100) : null, d: b-a};',
    ],

    'fraction-calculator' => [
        'fields'  => [
            ['id' => 'n1', 'label' => 'numerator', 'default' => 1],
            ['id' => 'd1', 'label' => 'denominator', 'default' => 2],
            ['id' => 'op', 'label' => 'operation', 'type' => 'select', 'default' => '+',
             'options' => ['+' => '+', '-' => '−', '*' => '×', '/' => '÷']],
            ['id' => 'n2', 'label' => 'numerator', 'default' => 1],
            ['id' => 'd2', 'label' => 'denominator', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'f', 'label' => 'result_fraction', 'text' => true],
            ['id' => 'v', 'label' => 'decimal'],
        ],
        'js' => 'if(!d1||!d2) return {f:null,v:null};
                 var n,d;
                 if(op==="+"){n=n1*d2+n2*d1; d=d1*d2;}
                 else if(op==="-"){n=n1*d2-n2*d1; d=d1*d2;}
                 else if(op==="*"){n=n1*n2; d=d1*d2;}
                 else {n=n1*d2; d=d1*n2;}
                 if(!d) return {f:null,v:null};
                 function g(x,y){x=Math.abs(x);y=Math.abs(y);while(y){var t=y;y=x%y;x=t;}return x||1;}
                 var k=g(n,d); n/=k; d/=k;
                 if(d<0){n=-n;d=-d;}
                 return {f: n + "/" + d, v: n/d};',
    ],

    'ratio-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 16],
            ['id' => 'b', 'label' => 'b', 'default' => 9],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'simplified', 'text' => true],
            ['id' => 'v', 'label' => 'decimal'],
        ],
        'js' => 'function g(x,y){x=Math.abs(x);y=Math.abs(y);while(y){var t=y;y=x%y;x=t;}return x||1;}
                 if(!a||!b) return {r:null,v:null};
                 var k=g(a,b);
                 return {r: (a/k) + ":" + (b/k), v: a/b};',
    ],

    'proportion-solver' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 3],
            ['id' => 'b', 'label' => 'b', 'default' => 4],
            ['id' => 'c', 'label' => 'c', 'default' => 9],
        ],
        'outputs' => [['id' => 'x', 'label' => 'x']],
        'js' => 'return {x: a ? (b*c/a) : null};',
    ],

    // ------------------------------------------------------------ statistics

    'average-calculator' => [
        'fields'  => [
            ['id' => 'list', 'label' => 'numbers_list', 'type' => 'text', 'default' => '4, 8, 15, 16, 23, 42'],
        ],
        'outputs' => [
            ['id' => 'mean', 'label' => 'mean'],
            ['id' => 'median', 'label' => 'median'],
            ['id' => 'sum', 'label' => 'sum'],
            ['id' => 'count', 'label' => 'count'],
            ['id' => 'min', 'label' => 'min'],
            ['id' => 'max', 'label' => 'max'],
        ],
        'js' => 'var v=String(list).split(/[^0-9eE+\-.]+/).map(Number).filter(function(x){return !isNaN(x);});
                 if(!v.length) return {};
                 var s=v.reduce(function(p,c){return p+c;},0);
                 var srt=v.slice().sort(function(p,c){return p-c;});
                 var mid=Math.floor(srt.length/2);
                 var med = srt.length%2 ? srt[mid] : (srt[mid-1]+srt[mid])/2;
                 return {mean:s/v.length, median:med, sum:s, count:v.length, min:srt[0], max:srt[srt.length-1]};',
    ],

    'standard-deviation-calculator' => [
        'fields'  => [
            ['id' => 'list', 'label' => 'numbers_list', 'type' => 'text', 'default' => '10, 12, 23, 23, 16, 23, 21, 16'],
        ],
        'outputs' => [
            ['id' => 'mean', 'label' => 'mean'],
            ['id' => 'vp', 'label' => 'variance_pop'],
            ['id' => 'sp', 'label' => 'sd_pop'],
            ['id' => 'ss', 'label' => 'sd_sample'],
        ],
        'js' => 'var v=String(list).split(/[^0-9eE+\-.]+/).map(Number).filter(function(x){return !isNaN(x);});
                 if(!v.length) return {};
                 var m=v.reduce(function(p,c){return p+c;},0)/v.length;
                 var sq=v.reduce(function(p,c){return p+(c-m)*(c-m);},0);
                 return {mean:m, vp:sq/v.length, sp:Math.sqrt(sq/v.length), ss: v.length>1 ? Math.sqrt(sq/(v.length-1)) : null};',
    ],

    // -------------------------------------------------------------- equations

    'quadratic-equation-solver' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 1],
            ['id' => 'b', 'label' => 'b', 'default' => -3],
            ['id' => 'c', 'label' => 'c', 'default' => 2],
        ],
        'outputs' => [
            ['id' => 'd', 'label' => 'discriminant'],
            ['id' => 'x1', 'label' => 'x1', 'text' => true],
            ['id' => 'x2', 'label' => 'x2', 'text' => true],
        ],
        'js' => 'if(!a) return {d:null,x1:null,x2:null};
                 var D=b*b-4*a*c;
                 if(D>=0){var r1=(-b+Math.sqrt(D))/(2*a), r2=(-b-Math.sqrt(D))/(2*a);
                   return {d:D, x1:String(Math.round(r1*1e6)/1e6), x2:String(Math.round(r2*1e6)/1e6)};}
                 var re=Math.round((-b/(2*a))*1e6)/1e6, im=Math.round((Math.sqrt(-D)/(2*a))*1e6)/1e6;
                 return {d:D, x1:re+" + "+im+"i", x2:re+" − "+im+"i"};',
    ],

    'linear-equation-solver' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 5],
            ['id' => 'b', 'label' => 'b', 'default' => -15],
        ],
        'outputs' => [['id' => 'x', 'label' => 'x']],
        'js' => 'return {x: a ? (-b/a) : null};',
    ],

    'system-of-equations-solver' => [
        'fields'  => [
            ['id' => 'a1', 'label' => 'a', 'default' => 2],
            ['id' => 'b1', 'label' => 'b', 'default' => 3],
            ['id' => 'c1', 'label' => 'c', 'default' => 12],
            ['id' => 'a2', 'label' => 'a2', 'default' => 4],
            ['id' => 'b2', 'label' => 'b2', 'default' => -1],
            ['id' => 'c2', 'label' => 'c2', 'default' => 5],
        ],
        'outputs' => [
            ['id' => 'x', 'label' => 'x'],
            ['id' => 'y', 'label' => 'y'],
        ],
        'js' => 'var det=a1*b2-a2*b1;
                 if(!det) return {x:null,y:null};
                 return {x:(c1*b2-c2*b1)/det, y:(a1*c2-a2*c1)/det};',
    ],

    // ---------------------------------------------------------- number theory

    'gcd-lcm-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 48],
            ['id' => 'b', 'label' => 'b', 'default' => 18],
        ],
        'outputs' => [
            ['id' => 'g', 'label' => 'gcd'],
            ['id' => 'l', 'label' => 'lcm'],
        ],
        'js' => 'function g(x,y){x=Math.abs(x);y=Math.abs(y);while(y){var t=y;y=x%y;x=t;}return x;}
                 var G=g(a,b);
                 return {g:G, l: G ? Math.abs(a*b)/G : null};',
    ],

    'prime-number-checker' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'number', 'default' => 97],
        ],
        'outputs' => [
            ['id' => 'p', 'label' => 'is_prime', 'text' => true],
            ['id' => 'f', 'label' => 'factors', 'text' => true],
        ],
        'js' => 'n=Math.floor(Math.abs(n));
                 if(n<2) return {p:"—", f:"—"};
                 var m=n, fs=[];
                 for(var d=2; d*d<=m; d++){ while(m%d===0){ fs.push(d); m/=d; } }
                 if(m>1) fs.push(m);
                 return {p: fs.length===1 ? "✓" : "✗", f: fs.join(" × ")};',
    ],

    'factorial-calculator' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'number', 'default' => 10],
        ],
        'outputs' => [['id' => 'f', 'label' => 'factorial', 'text' => true]],
        'js' => 'n=Math.floor(n);
                 if(n<0||n>170) return {f:"—"};
                 var r=1; for(var i=2;i<=n;i++) r*=i;
                 return {f: r.toLocaleString("en-US", {maximumFractionDigits:0})};',
    ],

    'combinations-permutations-calculator' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'n', 'default' => 10],
            ['id' => 'r', 'label' => 'r', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'c', 'label' => 'combinations'],
            ['id' => 'p', 'label' => 'permutations'],
        ],
        'js' => 'n=Math.floor(n); r=Math.floor(r);
                 if(n<0||r<0||r>n) return {c:null,p:null};
                 var P=1; for(var i=0;i<r;i++) P*=(n-i);
                 var R=1; for(var j=2;j<=r;j++) R*=j;
                 return {c:P/R, p:P};',
    ],

    // ------------------------------------------------------- powers and roots

    'exponent-calculator' => [
        'fields'  => [
            ['id' => 'b', 'label' => 'base', 'default' => 2],
            ['id' => 'e', 'label' => 'exponent', 'default' => 10],
        ],
        'outputs' => [['id' => 'r', 'label' => 'result']],
        'js' => 'return {r: Math.pow(b,e)};',
    ],

    'root-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 81],
            ['id' => 'n', 'label' => 'degree', 'default' => 2],
        ],
        'outputs' => [['id' => 'r', 'label' => 'root']],
        'js' => 'if(!n) return {r:null};
                 if(x<0 && n%2===0) return {r:null};
                 var s = x<0 ? -1 : 1;
                 return {r: s*Math.pow(Math.abs(x), 1/n)};',
    ],

    'logarithm-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 1000],
            ['id' => 'b', 'label' => 'base', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'log_base'],
            ['id' => 'n', 'label' => 'ln'],
            ['id' => 'l', 'label' => 'log10'],
        ],
        'js' => 'if(x<=0) return {};
                 return {r: (b>0 && b!==1) ? Math.log(x)/Math.log(b) : null, n: Math.log(x), l: Math.log(x)/Math.LN10};',
    ],

    // ------------------------------------------------------------- formatting

    'rounding-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 3.14159],
            ['id' => 'd', 'label' => 'decimals', 'default' => 2],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'rounded', 'text' => true],
            ['id' => 'f', 'label' => 'floor'],
            ['id' => 'c', 'label' => 'ceil'],
        ],
        'js' => 'd=Math.max(0,Math.min(15,Math.floor(d)));
                 return {r: x.toFixed(d), f: Math.floor(x), c: Math.ceil(x)};',
    ],

    'scientific-notation-converter' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 0.00042],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'scientific', 'text' => true],
            ['id' => 'e', 'label' => 'engineering', 'text' => true],
        ],
        'js' => 'if(x===0) return {s:"0", e:"0"};
                 var ex=Math.floor(Math.log10(Math.abs(x)));
                 var mant=x/Math.pow(10,ex);
                 var ee=Math.floor(ex/3)*3;
                 var em=x/Math.pow(10,ee);
                 return {s: (Math.round(mant*1e6)/1e6) + " × 10^" + ex,
                         e: (Math.round(em*1e6)/1e6) + " × 10^" + ee};',
    ],

    'number-base-converter' => [
        'fields'  => [
            ['id' => 'v', 'label' => 'number', 'type' => 'text', 'default' => '255'],
            ['id' => 'f', 'label' => 'from_base', 'type' => 'select', 'default' => '10',
             'options' => ['2' => '2', '8' => '8', '10' => '10', '16' => '16']],
        ],
        'outputs' => [
            ['id' => 'b', 'label' => 'binary', 'text' => true],
            ['id' => 'o', 'label' => 'octal', 'text' => true],
            ['id' => 'd', 'label' => 'decimal', 'text' => true],
            ['id' => 'h', 'label' => 'hex', 'text' => true],
        ],
        'js' => 'var n=parseInt(String(v).trim(), Number(f));
                 if(isNaN(n)) return {};
                 return {b:n.toString(2), o:n.toString(8), d:n.toString(10), h:n.toString(16).toUpperCase()};',
    ],

    'roman-numeral-converter' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'number', 'default' => 2024],
        ],
        'outputs' => [['id' => 'r', 'label' => 'roman', 'text' => true]],
        'js' => 'n=Math.floor(n);
                 if(n<1||n>3999) return {r:"—"};
                 var m=[[1000,"M"],[900,"CM"],[500,"D"],[400,"CD"],[100,"C"],[90,"XC"],
                        [50,"L"],[40,"XL"],[10,"X"],[9,"IX"],[5,"V"],[4,"IV"],[1,"I"]];
                 var s="";
                 for(var i=0;i<m.length;i++){ while(n>=m[i][0]){ s+=m[i][1]; n-=m[i][0]; } }
                 return {r:s};',
    ],

    // -------------------------------------------------------------- geometry

    'circle-calculator' => [
        'fields'  => [['id' => 'r', 'label' => 'radius', 'default' => 5]],
        'outputs' => [
            ['id' => 'a', 'label' => 'area'],
            ['id' => 'c', 'label' => 'circumference'],
            ['id' => 'd', 'label' => 'diameter'],
        ],
        'js' => 'return {a: Math.PI*r*r, c: 2*Math.PI*r, d: 2*r};',
    ],

    'triangle-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'side_a', 'default' => 3],
            ['id' => 'b', 'label' => 'side_b', 'default' => 4],
            ['id' => 'c', 'label' => 'side_c', 'default' => 5],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
            ['id' => 'h', 'label' => 'height_to_a'],
        ],
        'js' => 'if(a+b<=c||a+c<=b||b+c<=a) return {};
                 var sp=(a+b+c)/2;
                 var ar=Math.sqrt(sp*(sp-a)*(sp-b)*(sp-c));
                 return {s:ar, p:a+b+c, h: a ? (2*ar/a) : null};',
    ],

    'pythagorean-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'leg_a', 'default' => 3],
            ['id' => 'b', 'label' => 'leg_b', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'c', 'label' => 'hypotenuse'],
            ['id' => 's', 'label' => 'area'],
        ],
        'js' => 'return {c: Math.sqrt(a*a+b*b), s: a*b/2};',
    ],

    'rectangle-calculator' => [
        'fields'  => [
            ['id' => 'w', 'label' => 'width', 'default' => 8],
            ['id' => 'h', 'label' => 'height', 'default' => 6],
        ],
        'outputs' => [
            ['id' => 'a', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
            ['id' => 'd', 'label' => 'diagonal'],
        ],
        'js' => 'return {a:w*h, p:2*(w+h), d:Math.sqrt(w*w+h*h)};',
    ],

    'sphere-calculator' => [
        'fields'  => [['id' => 'r', 'label' => 'radius', 'default' => 5]],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: 4/3*Math.PI*r*r*r, s: 4*Math.PI*r*r};',
    ],

    'cylinder-calculator' => [
        'fields'  => [
            ['id' => 'r', 'label' => 'radius', 'default' => 3],
            ['id' => 'h', 'label' => 'height', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: Math.PI*r*r*h, s: 2*Math.PI*r*(r+h)};',
    ],

    'cone-calculator' => [
        'fields'  => [
            ['id' => 'r', 'label' => 'radius', 'default' => 3],
            ['id' => 'h', 'label' => 'height', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 'l', 'label' => 'slant'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'var l=Math.sqrt(r*r+h*h);
                 return {v: Math.PI*r*r*h/3, l:l, s: Math.PI*r*(r+l)};',
    ],

    'trapezoid-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'base_a', 'default' => 8],
            ['id' => 'b', 'label' => 'base_b', 'default' => 5],
            ['id' => 'h', 'label' => 'height', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'm', 'label' => 'midline'],
        ],
        'js' => 'return {s: (a+b)/2*h, m: (a+b)/2};',
    ],

    // ------------------------------------------------------ coordinate geometry

    'distance-between-points' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => 0],
            ['id' => 'y1', 'label' => 'y1', 'default' => 0],
            ['id' => 'x2', 'label' => 'x2', 'default' => 3],
            ['id' => 'y2', 'label' => 'y2', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'd', 'label' => 'distance'],
            ['id' => 'm', 'label' => 'midpoint', 'text' => true],
        ],
        'js' => 'return {d: Math.hypot(x2-x1, y2-y1),
                         m: "(" + ((x1+x2)/2) + "; " + ((y1+y2)/2) + ")"};',
    ],

    'slope-calculator' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => 1],
            ['id' => 'y1', 'label' => 'y1', 'default' => 2],
            ['id' => 'x2', 'label' => 'x2', 'default' => 4],
            ['id' => 'y2', 'label' => 'y2', 'default' => 8],
        ],
        'outputs' => [
            ['id' => 'k', 'label' => 'slope'],
            ['id' => 'b', 'label' => 'intercept'],
            ['id' => 'a', 'label' => 'angle_deg'],
        ],
        'js' => 'if(x1===x2) return {};
                 var k=(y2-y1)/(x2-x1);
                 return {k:k, b: y1-k*x1, a: Math.atan(k)*180/Math.PI};',
    ],

    'midpoint-calculator' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => -2],
            ['id' => 'y1', 'label' => 'y1', 'default' => 3],
            ['id' => 'x2', 'label' => 'x2', 'default' => 6],
            ['id' => 'y2', 'label' => 'y2', 'default' => 7],
        ],
        'outputs' => [
            ['id' => 'mx', 'label' => 'x'],
            ['id' => 'my', 'label' => 'y'],
        ],
        'js' => 'return {mx:(x1+x2)/2, my:(y1+y2)/2};',
    ],

    // ----------------------------------------------------------- applied math

    'simple-interest-calculator' => [
        'fields'  => [
            ['id' => 'p', 'label' => 'principal', 'default' => 1000],
            ['id' => 'r', 'label' => 'rate_percent', 'default' => 5],
            ['id' => 't', 'label' => 'years', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'i', 'label' => 'interest'],
            ['id' => 'a', 'label' => 'total'],
        ],
        'js' => 'var i=p*r*t/100; return {i:i, a:p+i};',
    ],

    'compound-interest-calculator' => [
        'fields'  => [
            ['id' => 'p', 'label' => 'principal', 'default' => 1000],
            ['id' => 'r', 'label' => 'rate_percent', 'default' => 5],
            ['id' => 't', 'label' => 'years', 'default' => 10],
            ['id' => 'n', 'label' => 'per_year', 'default' => 12],
        ],
        'outputs' => [
            ['id' => 'a', 'label' => 'total'],
            ['id' => 'i', 'label' => 'interest'],
        ],
        'js' => 'if(!n) return {};
                 var A=p*Math.pow(1+(r/100)/n, n*t);
                 return {a:A, i:A-p};',
    ],

    // ============================ extra set: plane geometry ==================

    'square-calculator' => [
        'fields'  => [['id' => 'a', 'label' => 'side', 'default' => 6]],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
            ['id' => 'd', 'label' => 'diagonal'],
        ],
        'js' => 'return {s: a*a, p: 4*a, d: a*Math.SQRT2};',
    ],

    'parallelogram-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'side_a', 'default' => 8],
            ['id' => 'b', 'label' => 'side_b', 'default' => 5],
            ['id' => 'h', 'label' => 'height', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
        ],
        'js' => 'return {s: a*h, p: 2*(a+b)};',
    ],

    'rhombus-calculator' => [
        'fields'  => [
            ['id' => 'd1', 'label' => 'diagonal_1', 'default' => 10],
            ['id' => 'd2', 'label' => 'diagonal_2', 'default' => 6],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'a', 'label' => 'side'],
            ['id' => 'p', 'label' => 'perimeter'],
        ],
        'js' => 'var side = Math.sqrt((d1/2)*(d1/2) + (d2/2)*(d2/2));
                 return {s: d1*d2/2, a: side, p: 4*side};',
    ],

    'regular-polygon-calculator' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'sides_count', 'default' => 6],
            ['id' => 'a', 'label' => 'side', 'default' => 5],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
            ['id' => 'i', 'label' => 'interior_angle'],
            ['id' => 'ap', 'label' => 'apothem'],
        ],
        'js' => 'n = Math.floor(n);
                 if (n < 3) return {};
                 var ap = a / (2*Math.tan(Math.PI/n));
                 return {s: n*a*ap/2, p: n*a, i: (n-2)*180/n, ap: ap};',
    ],

    'ellipse-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'semi_major', 'default' => 6],
            ['id' => 'b', 'label' => 'semi_minor', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'area'],
            ['id' => 'p', 'label' => 'perimeter'],
        ],
        'js' => 'var h = Math.pow(a-b, 2) / Math.pow(a+b, 2);
                 return {s: Math.PI*a*b,
                         p: Math.PI*(a+b)*(1 + 3*h/(10 + Math.sqrt(4 - 3*h)))};',
    ],

    'circle-sector-calculator' => [
        'fields'  => [
            ['id' => 'r', 'label' => 'radius', 'default' => 5],
            ['id' => 'ang', 'label' => 'angle_deg', 'default' => 60],
        ],
        'outputs' => [
            ['id' => 'l', 'label' => 'arc_length'],
            ['id' => 's', 'label' => 'sector_area'],
            ['id' => 'c', 'label' => 'chord'],
        ],
        'js' => 'var rad = ang*Math.PI/180;
                 return {l: r*rad, s: r*r*rad/2, c: 2*r*Math.sin(rad/2)};',
    ],

    // ============================ extra set: solids ==========================

    'cube-calculator' => [
        'fields'  => [['id' => 'a', 'label' => 'edge', 'default' => 4]],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
            ['id' => 'd', 'label' => 'space_diagonal'],
        ],
        'js' => 'return {v: a*a*a, s: 6*a*a, d: a*Math.sqrt(3)};',
    ],

    'cuboid-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'length', 'default' => 5],
            ['id' => 'b', 'label' => 'width', 'default' => 4],
            ['id' => 'c', 'label' => 'height', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
            ['id' => 'd', 'label' => 'space_diagonal'],
        ],
        'js' => 'return {v: a*b*c, s: 2*(a*b + b*c + a*c), d: Math.sqrt(a*a + b*b + c*c)};',
    ],

    'square-pyramid-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'base_side', 'default' => 6],
            ['id' => 'h', 'label' => 'height', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 'l', 'label' => 'slant'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'var l = Math.sqrt(h*h + (a/2)*(a/2));
                 return {v: a*a*h/3, l: l, s: a*a + 2*a*l};',
    ],

    'prism-calculator' => [
        'fields'  => [
            ['id' => 'ba', 'label' => 'base_area', 'default' => 12],
            ['id' => 'bp', 'label' => 'base_perimeter', 'default' => 14],
            ['id' => 'h', 'label' => 'height', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: ba*h, s: 2*ba + bp*h};',
    ],

    'hemisphere-calculator' => [
        'fields'  => [['id' => 'r', 'label' => 'radius', 'default' => 5]],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: 2/3*Math.PI*r*r*r, s: 3*Math.PI*r*r};',
    ],

    'torus-calculator' => [
        'fields'  => [
            ['id' => 'R', 'label' => 'major_radius', 'default' => 10],
            ['id' => 'r', 'label' => 'minor_radius', 'default' => 3],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'volume'],
            ['id' => 's', 'label' => 'surface'],
        ],
        'js' => 'return {v: 2*Math.PI*Math.PI*R*r*r, s: 4*Math.PI*Math.PI*R*r};',
    ],

    // ============================ extra set: sequences ========================

    'arithmetic-sequence-calculator' => [
        'fields'  => [
            ['id' => 'a1', 'label' => 'first_term', 'default' => 2],
            ['id' => 'd', 'label' => 'common_difference', 'default' => 3],
            ['id' => 'n', 'label' => 'term_number', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'an', 'label' => 'nth_term'],
            ['id' => 's', 'label' => 'sum_n'],
        ],
        'js' => 'n = Math.floor(n);
                 if (n < 1) return {};
                 var an = a1 + (n-1)*d;
                 return {an: an, s: n*(a1 + an)/2};',
    ],

    'geometric-sequence-calculator' => [
        'fields'  => [
            ['id' => 'a1', 'label' => 'first_term', 'default' => 3],
            ['id' => 'q', 'label' => 'common_ratio', 'default' => 2],
            ['id' => 'n', 'label' => 'term_number', 'default' => 8],
        ],
        'outputs' => [
            ['id' => 'an', 'label' => 'nth_term'],
            ['id' => 's', 'label' => 'sum_n'],
        ],
        'js' => 'n = Math.floor(n);
                 if (n < 1) return {};
                 var an = a1*Math.pow(q, n-1);
                 return {an: an, s: q === 1 ? a1*n : a1*(Math.pow(q, n) - 1)/(q - 1)};',
    ],

    'fibonacci-calculator' => [
        'fields'  => [['id' => 'n', 'label' => 'term_number', 'default' => 20]],
        'outputs' => [
            ['id' => 'f', 'label' => 'nth_term'],
            ['id' => 's', 'label' => 'sum_n'],
        ],
        'js' => 'n = Math.floor(n);
                 if (n < 1 || n > 78) return {};
                 var a = 0, b = 1, sum = 0;
                 for (var i = 1; i <= n; i++) { sum += b; var t = a + b; a = b; b = t; }
                 return {f: a, s: sum};',
    ],

    // ============================ extra set: statistics =======================

    'weighted-average-calculator' => [
        'fields'  => [
            ['id' => 'vals', 'label' => 'values_list', 'type' => 'text', 'default' => '90, 75, 60'],
            ['id' => 'wts', 'label' => 'weights_list', 'type' => 'text', 'default' => '3, 2, 1'],
        ],
        'outputs' => [
            ['id' => 'w', 'label' => 'weighted_mean'],
            ['id' => 'tw', 'label' => 'total_weight'],
        ],
        'js' => 'function nums(s){return String(s).split(/[^0-9eE+\-.]+/).map(Number).filter(function(x){return !isNaN(x);});}
                 var v = nums(vals), w = nums(wts);
                 var k = Math.min(v.length, w.length);
                 if (!k) return {};
                 var sp = 0, sw = 0;
                 for (var i = 0; i < k; i++) { sp += v[i]*w[i]; sw += w[i]; }
                 return {w: sw ? sp/sw : null, tw: sw};',
    ],

    'mode-range-calculator' => [
        'fields'  => [['id' => 'list', 'label' => 'numbers_list', 'type' => 'text', 'default' => '3, 7, 7, 2, 9, 7, 2']],
        'outputs' => [
            ['id' => 'mo', 'label' => 'mode', 'text' => true],
            ['id' => 'rg', 'label' => 'range'],
            ['id' => 'mn', 'label' => 'min'],
            ['id' => 'mx', 'label' => 'max'],
        ],
        'js' => 'var v = String(list).split(/[^0-9eE+\-.]+/).map(Number).filter(function(x){return !isNaN(x);});
                 if (!v.length) return {};
                 var c = {}, best = 0;
                 v.forEach(function(x){ c[x] = (c[x]||0) + 1; if (c[x] > best) best = c[x]; });
                 var modes = Object.keys(c).filter(function(k){ return c[k] === best; });
                 var srt = v.slice().sort(function(p,q){ return p-q; });
                 return {mo: best > 1 ? modes.join(", ") : "—",
                         rg: srt[srt.length-1] - srt[0], mn: srt[0], mx: srt[srt.length-1]};',
    ],

    'z-score-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 85],
            ['id' => 'm', 'label' => 'mean', 'default' => 70],
            ['id' => 's', 'label' => 'sd_input', 'default' => 10],
        ],
        'outputs' => [['id' => 'z', 'label' => 'z_score']],
        'js' => 'return {z: s ? (x - m)/s : null};',
    ],

    'probability-calculator' => [
        'fields'  => [
            ['id' => 'f', 'label' => 'favorable', 'default' => 3],
            ['id' => 't', 'label' => 'total_outcomes', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'p', 'label' => 'probability'],
            ['id' => 'pc', 'label' => 'probability_percent'],
            ['id' => 'o', 'label' => 'odds', 'text' => true],
        ],
        'js' => 'if (!t || f < 0 || f > t) return {};
                 return {p: f/t, pc: f/t*100, o: f + " : " + (t - f)};',
    ],

    'binomial-probability-calculator' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'trials', 'default' => 10],
            ['id' => 'k', 'label' => 'successes', 'default' => 3],
            ['id' => 'p', 'label' => 'prob_success', 'default' => 0.5],
        ],
        'outputs' => [
            ['id' => 'r', 'label' => 'probability'],
            ['id' => 'rc', 'label' => 'probability_percent'],
        ],
        'js' => 'n = Math.floor(n); k = Math.floor(k);
                 if (k < 0 || k > n || p < 0 || p > 1) return {};
                 var C = 1;
                 for (var i = 0; i < k; i++) C = C*(n-i)/(i+1);
                 var r = C*Math.pow(p, k)*Math.pow(1-p, n-k);
                 return {r: r, rc: r*100};',
    ],

    'exponential-growth-calculator' => [
        'fields'  => [
            ['id' => 'p0', 'label' => 'initial', 'default' => 1000],
            ['id' => 'r', 'label' => 'growth_rate', 'default' => 7],
            ['id' => 't', 'label' => 'time_periods', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'f', 'label' => 'final_amount'],
            ['id' => 'g', 'label' => 'difference'],
        ],
        'js' => 'var f = p0*Math.pow(1 + r/100, t);
                 return {f: f, g: f - p0};',
    ],

    'half-life-calculator' => [
        'fields'  => [
            ['id' => 'n0', 'label' => 'initial', 'default' => 100],
            ['id' => 'hl', 'label' => 'half_life', 'default' => 5],
            ['id' => 't', 'label' => 'elapsed', 'default' => 15],
        ],
        'outputs' => [
            ['id' => 'n', 'label' => 'remaining'],
            ['id' => 'pc', 'label' => 'remaining_percent'],
        ],
        'js' => 'if (!hl) return {};
                 var n = n0*Math.pow(0.5, t/hl);
                 return {n: n, pc: n0 ? n/n0*100 : null};',
    ],

    // ============================ extra set: everyday math ====================

    'percent-error-calculator' => [
        'fields'  => [
            ['id' => 'e', 'label' => 'exact_value', 'default' => 50],
            ['id' => 'a', 'label' => 'approx_value', 'default' => 47],
        ],
        'outputs' => [
            ['id' => 'p', 'label' => 'error_percent'],
            ['id' => 'd', 'label' => 'difference'],
        ],
        'js' => 'if (!e) return {};
                 return {p: Math.abs((a-e)/e)*100, d: a-e};',
    ],

    'discount-calculator' => [
        'fields'  => [
            ['id' => 'p', 'label' => 'price', 'default' => 1200],
            ['id' => 'd', 'label' => 'discount_percent', 'default' => 25],
        ],
        'outputs' => [
            ['id' => 'f', 'label' => 'final_price'],
            ['id' => 's', 'label' => 'saved'],
        ],
        'js' => 'var s = p*d/100;
                 return {f: p - s, s: s};',
    ],

    'vat-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'amount', 'default' => 1000],
            ['id' => 'r', 'label' => 'vat_rate', 'default' => 20],
        ],
        'outputs' => [
            ['id' => 'tax', 'label' => 'tax'],
            ['id' => 'gross', 'label' => 'gross'],
            ['id' => 'net', 'label' => 'net_from_gross'],
        ],
        'js' => 'return {tax: a*r/100, gross: a*(1 + r/100), net: a/(1 + r/100)};',
    ],

    'tip-calculator' => [
        'fields'  => [
            ['id' => 'b', 'label' => 'bill', 'default' => 800],
            ['id' => 'p', 'label' => 'tip_percent', 'default' => 10],
            ['id' => 'n', 'label' => 'people', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 't', 'label' => 'tip'],
            ['id' => 'tot', 'label' => 'total'],
            ['id' => 'pp', 'label' => 'per_person'],
        ],
        'js' => 'var t = b*p/100, tot = b + t;
                 return {t: t, tot: tot, pp: n > 0 ? tot/n : null};',
    ],

    'markup-margin-calculator' => [
        'fields'  => [
            ['id' => 'c', 'label' => 'cost', 'default' => 60],
            ['id' => 'p', 'label' => 'price', 'default' => 100],
        ],
        'outputs' => [
            ['id' => 'pr', 'label' => 'profit'],
            ['id' => 'mu', 'label' => 'markup_percent'],
            ['id' => 'mg', 'label' => 'margin_percent'],
        ],
        'js' => 'var pr = p - c;
                 return {pr: pr, mu: c ? pr/c*100 : null, mg: p ? pr/p*100 : null};',
    ],

    'unit-price-calculator' => [
        'fields'  => [
            ['id' => 'p', 'label' => 'price', 'default' => 150],
            ['id' => 'q', 'label' => 'quantity', 'default' => 2.5],
        ],
        'outputs' => [['id' => 'u', 'label' => 'unit_price']],
        'js' => 'return {u: q ? p/q : null};',
    ],

    'speed-distance-time-calculator' => [
        'fields'  => [
            ['id' => 'd', 'label' => 'distance', 'default' => 120],
            ['id' => 't', 'label' => 'time_hours', 'default' => 1.5],
        ],
        'outputs' => [
            ['id' => 's', 'label' => 'speed'],
            ['id' => 'ms', 'label' => 'speed_ms'],
        ],
        'js' => 'if (!t) return {};
                 var s = d/t;
                 return {s: s, ms: s*1000/3600};',
    ],

    // ============================ extra set: fractions & numbers ==============

    'improper-fraction-converter' => [
        'fields'  => [
            ['id' => 'w', 'label' => 'whole', 'default' => 2],
            ['id' => 'n', 'label' => 'numerator', 'default' => 3],
            ['id' => 'd', 'label' => 'denominator', 'default' => 4],
        ],
        'outputs' => [
            ['id' => 'f', 'label' => 'improper', 'text' => true],
            ['id' => 'v', 'label' => 'decimal'],
        ],
        'js' => 'if (!d) return {};
                 var sign = w < 0 ? -1 : 1;
                 var num = Math.abs(w)*Math.abs(d) + Math.abs(n);
                 return {f: (sign*num) + "/" + Math.abs(d), v: sign*num/Math.abs(d)};',
    ],

    'decimal-to-fraction-converter' => [
        'fields'  => [['id' => 'x', 'label' => 'decimal', 'default' => 0.375]],
        'outputs' => [
            ['id' => 'f', 'label' => 'result_fraction', 'text' => true],
            ['id' => 'm', 'label' => 'mixed', 'text' => true],
        ],
        'js' => 'if (!isFinite(x)) return {};
                 var sign = x < 0 ? -1 : 1, v = Math.abs(x);
                 var d = 1;
                 while (Math.abs(v*d - Math.round(v*d)) > 1e-9 && d < 1e9) d *= 10;
                 var n = Math.round(v*d);
                 function g(p,q){while(q){var t=q;q=p%q;p=t;}return p||1;}
                 var k = g(n,d); n/=k; d/=k;
                 var whole = Math.floor(n/d), rem = n - whole*d;
                 return {f: (sign*n) + "/" + d,
                         m: rem === 0 ? String(sign*whole) : (sign*whole) + " " + rem + "/" + d};',
    ],

    'fraction-to-percent-converter' => [
        'fields'  => [
            ['id' => 'n', 'label' => 'numerator', 'default' => 3],
            ['id' => 'd', 'label' => 'denominator', 'default' => 8],
        ],
        'outputs' => [
            ['id' => 'p', 'label' => 'percent'],
            ['id' => 'v', 'label' => 'decimal'],
        ],
        'js' => 'if (!d) return {};
                 return {p: n/d*100, v: n/d};',
    ],

    'modulo-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'dividend', 'default' => 17],
            ['id' => 'b', 'label' => 'divisor', 'default' => 5],
        ],
        'outputs' => [
            ['id' => 'q', 'label' => 'quotient'],
            ['id' => 'r', 'label' => 'remainder'],
        ],
        'js' => 'if (!b) return {};
                 return {q: Math.floor(a/b), r: a - Math.floor(a/b)*b};',
    ],

    'long-division-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'dividend', 'default' => 745],
            ['id' => 'b', 'label' => 'divisor', 'default' => 12],
        ],
        'outputs' => [
            ['id' => 'q', 'label' => 'quotient'],
            ['id' => 'r', 'label' => 'remainder'],
            ['id' => 'd', 'label' => 'decimal'],
        ],
        'js' => 'if (!b) return {};
                 var q = Math.trunc(a/b);
                 return {q: q, r: a - q*b, d: a/b};',
    ],

    'significant-figures-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'value', 'default' => 12345.678],
            ['id' => 'n', 'label' => 'sig_figs', 'default' => 3],
        ],
        'outputs' => [['id' => 'r', 'label' => 'rounded', 'text' => true]],
        'js' => 'n = Math.floor(n);
                 if (n < 1 || n > 21 || x === 0) return {r: x === 0 ? "0" : "—"};
                 return {r: Number(x.toPrecision(n)).toString()};',
    ],

    'divisor-calculator' => [
        'fields'  => [['id' => 'n', 'label' => 'number', 'default' => 60]],
        'outputs' => [
            ['id' => 'c', 'label' => 'divisors_count'],
            ['id' => 's', 'label' => 'divisors_sum'],
            ['id' => 'l', 'label' => 'divisors_list', 'text' => true],
        ],
        'js' => 'n = Math.floor(Math.abs(n));
                 if (n < 1 || n > 1e7) return {};
                 var ds = [];
                 for (var i = 1; i*i <= n; i++) {
                     if (n % i === 0) { ds.push(i); if (i !== n/i) ds.push(n/i); }
                 }
                 ds.sort(function(p,q){ return p-q; });
                 return {c: ds.length, s: ds.reduce(function(p,q){return p+q;}, 0), l: ds.join(", ")};',
    ],

    // ============================ extra set: algebra & trig ===================

    'quadratic-vertex-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'a', 'default' => 1],
            ['id' => 'b', 'label' => 'b', 'default' => -4],
            ['id' => 'c', 'label' => 'c', 'default' => 7],
        ],
        'outputs' => [
            ['id' => 'vx', 'label' => 'vertex_x'],
            ['id' => 'vy', 'label' => 'vertex_y'],
            ['id' => 'ax', 'label' => 'axis', 'text' => true],
        ],
        'js' => 'if (!a) return {};
                 var vx = -b/(2*a);
                 return {vx: vx, vy: a*vx*vx + b*vx + c,
                         ax: "x = " + (Math.round(vx*1e6)/1e6)};',
    ],

    'line-equation-calculator' => [
        'fields'  => [
            ['id' => 'x1', 'label' => 'x1', 'default' => 1],
            ['id' => 'y1', 'label' => 'y1', 'default' => 2],
            ['id' => 'x2', 'label' => 'x2', 'default' => 4],
            ['id' => 'y2', 'label' => 'y2', 'default' => 11],
        ],
        'outputs' => [
            ['id' => 'eq', 'label' => 'equation', 'text' => true],
            ['id' => 'k', 'label' => 'slope'],
            ['id' => 'b', 'label' => 'intercept'],
        ],
        'js' => 'if (x1 === x2) return {eq: "x = " + x1, k: null, b: null};
                 var k = (y2-y1)/(x2-x1), b = y1 - k*x1;
                 var kr = Math.round(k*1e4)/1e4, br = Math.round(b*1e4)/1e4;
                 return {eq: "y = " + kr + "x " + (br < 0 ? "− " + Math.abs(br) : "+ " + br), k: k, b: b};',
    ],

    'triangle-angles-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'side_a', 'default' => 5],
            ['id' => 'b', 'label' => 'side_b', 'default' => 6],
            ['id' => 'c', 'label' => 'side_c', 'default' => 7],
        ],
        'outputs' => [
            ['id' => 'A', 'label' => 'angle_a'],
            ['id' => 'B', 'label' => 'angle_b'],
            ['id' => 'C', 'label' => 'angle_c'],
        ],
        'js' => 'if (a+b<=c || a+c<=b || b+c<=a) return {};
                 var deg = 180/Math.PI;
                 var A = Math.acos((b*b + c*c - a*a)/(2*b*c))*deg;
                 var B = Math.acos((a*a + c*c - b*b)/(2*a*c))*deg;
                 return {A: A, B: B, C: 180 - A - B};',
    ],

    'law-of-sines-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'side_a', 'default' => 10],
            ['id' => 'A', 'label' => 'angle_a', 'default' => 30],
            ['id' => 'B', 'label' => 'angle_b', 'default' => 45],
        ],
        'outputs' => [
            ['id' => 'b', 'label' => 'side_b'],
            ['id' => 'C', 'label' => 'angle_c'],
            ['id' => 'c', 'label' => 'side_c'],
        ],
        'js' => 'var rad = Math.PI/180;
                 if (A <= 0 || B <= 0 || A + B >= 180) return {};
                 var k = a/Math.sin(A*rad), C = 180 - A - B;
                 return {b: k*Math.sin(B*rad), C: C, c: k*Math.sin(C*rad)};',
    ],

    'trigonometry-calculator' => [
        'fields'  => [['id' => 'ang', 'label' => 'angle_deg', 'default' => 30]],
        'outputs' => [
            ['id' => 's', 'label' => 'sin'],
            ['id' => 'c', 'label' => 'cos'],
            ['id' => 't', 'label' => 'tan'],
        ],
        'js' => 'var r = ang*Math.PI/180;
                 var cs = Math.cos(r);
                 return {s: Math.sin(r), c: cs,
                         t: Math.abs(cs) < 1e-12 ? null : Math.tan(r)};',
    ],

    'inverse-trigonometry-calculator' => [
        'fields'  => [['id' => 'x', 'label' => 'value', 'default' => 0.5]],
        'outputs' => [
            ['id' => 'as', 'label' => 'asin'],
            ['id' => 'ac', 'label' => 'acos'],
            ['id' => 'at', 'label' => 'atan'],
        ],
        'js' => 'var deg = 180/Math.PI;
                 return {as: Math.abs(x) <= 1 ? Math.asin(x)*deg : null,
                         ac: Math.abs(x) <= 1 ? Math.acos(x)*deg : null,
                         at: Math.atan(x)*deg};',
    ],

    'degrees-radians-converter' => [
        'fields'  => [['id' => 'd', 'label' => 'degrees', 'default' => 180]],
        'outputs' => [
            ['id' => 'r', 'label' => 'radians'],
            ['id' => 'p', 'label' => 'in_pi', 'text' => true],
        ],
        'js' => 'return {r: d*Math.PI/180, p: (Math.round((d/180)*1e4)/1e4) + "π"};',
    ],

    // ============================ extra set: vectors & matrices ===============

    'vector-magnitude-calculator' => [
        'fields'  => [
            ['id' => 'x', 'label' => 'vx', 'default' => 3],
            ['id' => 'y', 'label' => 'vy', 'default' => 4],
            ['id' => 'z', 'label' => 'vz', 'default' => 0],
        ],
        'outputs' => [
            ['id' => 'm', 'label' => 'magnitude'],
            ['id' => 'u', 'label' => 'unit_vector', 'text' => true],
        ],
        'js' => 'var m = Math.sqrt(x*x + y*y + z*z);
                 if (!m) return {m: 0, u: "—"};
                 var f = function(v){ return Math.round(v/m*1e4)/1e4; };
                 return {m: m, u: "(" + f(x) + "; " + f(y) + "; " + f(z) + ")"};',
    ],

    'dot-product-calculator' => [
        'fields'  => [
            ['id' => 'ax', 'label' => 'ax', 'default' => 1],
            ['id' => 'ay', 'label' => 'ay', 'default' => 2],
            ['id' => 'az', 'label' => 'az', 'default' => 3],
            ['id' => 'bx', 'label' => 'bx', 'default' => 4],
            ['id' => 'by', 'label' => 'by', 'default' => -5],
            ['id' => 'bz', 'label' => 'bz', 'default' => 6],
        ],
        'outputs' => [
            ['id' => 'd', 'label' => 'dot'],
            ['id' => 'ang', 'label' => 'angle_between'],
        ],
        'js' => 'var d = ax*bx + ay*by + az*bz;
                 var ma = Math.sqrt(ax*ax + ay*ay + az*az);
                 var mb = Math.sqrt(bx*bx + by*by + bz*bz);
                 var cs = (ma && mb) ? Math.max(-1, Math.min(1, d/(ma*mb))) : null;
                 return {d: d, ang: cs === null ? null : Math.acos(cs)*180/Math.PI};',
    ],

    'cross-product-calculator' => [
        'fields'  => [
            ['id' => 'ax', 'label' => 'ax', 'default' => 1],
            ['id' => 'ay', 'label' => 'ay', 'default' => 2],
            ['id' => 'az', 'label' => 'az', 'default' => 3],
            ['id' => 'bx', 'label' => 'bx', 'default' => 4],
            ['id' => 'by', 'label' => 'by', 'default' => 5],
            ['id' => 'bz', 'label' => 'bz', 'default' => 6],
        ],
        'outputs' => [
            ['id' => 'v', 'label' => 'cross_vector', 'text' => true],
            ['id' => 'm', 'label' => 'magnitude'],
        ],
        'js' => 'var cx = ay*bz - az*by, cy = az*bx - ax*bz, cz = ax*by - ay*bx;
                 return {v: "(" + cx + "; " + cy + "; " + cz + ")",
                         m: Math.sqrt(cx*cx + cy*cy + cz*cz)};',
    ],

    'matrix-2x2-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'm11', 'default' => 4],
            ['id' => 'b', 'label' => 'm12', 'default' => 7],
            ['id' => 'c', 'label' => 'm21', 'default' => 2],
            ['id' => 'd', 'label' => 'm22', 'default' => 6],
        ],
        'outputs' => [
            ['id' => 'det', 'label' => 'determinant'],
            ['id' => 'tr', 'label' => 'trace'],
            ['id' => 'inv', 'label' => 'inverse', 'text' => true],
        ],
        'js' => 'var det = a*d - b*c;
                 if (!det) return {det: 0, tr: a + d, inv: "—"};
                 var f = function(v){ return Math.round(v/det*1e4)/1e4; };
                 return {det: det, tr: a + d,
                         inv: "[" + f(d) + " " + f(-b) + "; " + f(-c) + " " + f(a) + "]"};',
    ],

    'matrix-3x3-determinant-calculator' => [
        'fields'  => [
            ['id' => 'a', 'label' => 'm11', 'default' => 1],
            ['id' => 'b', 'label' => 'm12', 'default' => 2],
            ['id' => 'c', 'label' => 'm13', 'default' => 3],
            ['id' => 'd', 'label' => 'm21', 'default' => 4],
            ['id' => 'e', 'label' => 'm22', 'default' => 5],
            ['id' => 'f', 'label' => 'm23', 'default' => 6],
            ['id' => 'g', 'label' => 'm31', 'default' => 7],
            ['id' => 'h', 'label' => 'm32', 'default' => 8],
            ['id' => 'i', 'label' => 'm33', 'default' => 10],
        ],
        'outputs' => [
            ['id' => 'det', 'label' => 'determinant'],
            ['id' => 'tr', 'label' => 'trace'],
        ],
        'js' => 'return {det: a*(e*i - f*h) - b*(d*i - f*g) + c*(d*h - e*g), tr: a + e + i};',
    ],

    // ============================ extra set: unit converters ==================

    'length-unit-converter' => [
        'fields'  => [
            ['id' => 'v', 'label' => 'value', 'default' => 100],
            ['id' => 'f', 'label' => 'from_unit', 'type' => 'select', 'default' => 'm',
             'options' => ['mm' => 'mm', 'cm' => 'cm', 'm' => 'm', 'km' => 'km', 'in' => 'in', 'ft' => 'ft', 'yd' => 'yd', 'mi' => 'mi']],
        ],
        'outputs' => [
            ['id' => 'm', 'label' => 'meters'],
            ['id' => 'km', 'label' => 'kilometers'],
            ['id' => 'ft', 'label' => 'feet'],
            ['id' => 'mi', 'label' => 'miles'],
        ],
        'js' => 'var k = {mm:0.001, cm:0.01, m:1, km:1000, "in":0.0254, ft:0.3048, yd:0.9144, mi:1609.344};
                 var meters = v*(k[f] || 1);
                 return {m: meters, km: meters/1000, ft: meters/0.3048, mi: meters/1609.344};',
    ],

    'mass-unit-converter' => [
        'fields'  => [
            ['id' => 'v', 'label' => 'value', 'default' => 70],
            ['id' => 'f', 'label' => 'from_unit', 'type' => 'select', 'default' => 'kg',
             'options' => ['g' => 'g', 'kg' => 'kg', 't' => 't', 'oz' => 'oz', 'lb' => 'lb']],
        ],
        'outputs' => [
            ['id' => 'kg', 'label' => 'kilograms'],
            ['id' => 'g', 'label' => 'grams'],
            ['id' => 'lb', 'label' => 'pounds'],
            ['id' => 'oz', 'label' => 'ounces'],
        ],
        'js' => 'var k = {g:0.001, kg:1, t:1000, oz:0.028349523125, lb:0.45359237};
                 var kg = v*(k[f] || 1);
                 return {kg: kg, g: kg*1000, lb: kg/0.45359237, oz: kg/0.028349523125};',
    ],

];
