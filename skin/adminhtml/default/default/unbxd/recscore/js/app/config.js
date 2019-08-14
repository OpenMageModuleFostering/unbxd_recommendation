define([], 
function(){

	var Config = {
		pdn:true,
		maxSuggestions:16,
		maxPopularProducts:10,
		site:"",


		tooltips:{
			topqueries:"Top queries refer to the best performing search queries for your site. Enabling this will make sure your visitors see these queries first within your autocomplete widget. Preview template to see it in action",
			keywords:"We generate intelligent keyword suggestions & combinations based on your catalog & worldwide shopping data. Preview template to see it in action",
			searchscope:"Enable to display scope suggestions within the autosuggest widget",
			popularProducts:"Display thumbnails of popular products within the autocomplete widget. Preview template to see it in action",
			unbxdAttribute:"Unbxd attributes are fields which are identified by the Unbxd Smartengage Engine and are required for our algorithms and reporting",
			sitekey:"Receive the Site Key in a mail or find it in the Info Section of the Unbxd Dashboard by providing your Site URL after creating an Account on Unbxd Dashboard",
			secretkey:"Receive the Secret Key in a mail or find it in the Accounts Section of the Unbxd Dashboard by providing your Site URL after creating an Account on Unbxd Dashboard",

		},

		unbxdDimensions : [
	        {
	            "value": "brand",
	            "helpText": "The brand to which a product belongs to"
	        },
	        {
	            "value": "imageUrl",
	            "helpText": "Thumbnail - the URL of the image of a product that needs to be displayed in the recommendation widget, normally the thumnail"
	        },
	        {
	            "value": "title",
	            "helpText": "The title or name of the product e.g 'Blue Nike Shoe' "
	        },
	        {
	            "value": "price",
	            "helpText": "The display price of the product e.g $499"
	        },
	        {
	            "value": "color",
	            "helpText": "The color of the product e.g. 'Green t-shirt' "
	        },
	        {  
	            "value": "productUrl",
	            "helpText": "The material the product is made of e.g. 'Cotton trousers' "
	        },
	        {     
	            "value": "sellingPrice",
	            "helpText": "The display price of the product e.g $499" 
	        },    
	        {
	            "value": "size",
	            "helpText": "The size of the product e.g. 'Size 10' shoes"
	        }
  		],

  		unbxdMessages : {
        	authSuccess:"The Unbxd Module has been authenticated. Proceed to Catalog Configuration",
        	uploadSuccess:"Uploaded Catalog Data successfully. Proceed to Analytics integration",
        	syncSuccess:'Successfully uploaded'
    	},

    	autoSuggestDefaults:{
			autosuggest_status: true,
			autosuggest_skin: "green",
			autosuggest_template: 1,
			autosuggest_max_suggestion: 12,
			autosuggest_top_queries_status: false,
			autosuggest_keyword_status: true,
			autosuggest_search_scope_status: false,
			autosuggest_max_products: 2,
			skinBackground:'#FFF'
    	},

    	colorMap:{
    		'#FF8400':'orange',
    		'orange':'#FF8400',

    		"#29BD9F":'green',
    		'green':'#29BD9F',

    		'#6F6F6F':'grey',
    		'grey':'#6F6F6F',
    		
    		'#004C92':'blue',
    		'blue':'#004C92'
    	},

    	templateMap:{
    		'1column':1,
    		'2column-right':2,
    		'2column-left':3,
    		'1column-addToCart':4,

    		1:'1column',
    		2:'2column-right',
    		3:'2column-left',
    		4:'1column-addToCart'
    	},

    	mail:{
    		to:'support@unbxd.com'
    	},

    	status:{
    		requested:'requested',
    		ready:'complete'
    	},

    	states:{
    		analytics:false,
    		catalog:false,
    		credentials:false
    	}
			

	};

	return Config;
});