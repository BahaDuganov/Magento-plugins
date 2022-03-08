# PWA advanced-reviews

## Installation

1. Create an Empty PWA Studio Project by [Scaffolding](https://magento.github.io/pwa-studio/pwa-buildpack/scaffolding/)
2. `cd your_project/src` 
3. `mkdir @amasty` 
4.  copy module folder to `@amasty`
5.  run command from root directory
    - for development: `yarn add link:src/@amasty/social-login`  
    - for production: `yarn add file:src/@amasty/social-login`
     
6. Add call extend-intercept.js to local-intercept.js. Example:

```js
const amSocialLoginIntercept = require('@amasty/social-login/targets/extend-intercept');

function localIntercept(targets) {
    amSocialLoginIntercept(targets);
}

module.exports = localIntercept;
```
7. Run the Watch command: `yarn watch`.

### Usage

Here's an example of basic usage:

```js
import React from 'react';
import AmSocialLogin from '@amasty/social-login'

const MyComponent = (props) => {
  return (
    <div>
     <AmSocialLogin mode={'popup'} />
    </div>
  );
}
```

#### Props

|Prop name|Description|Default value|Example values|
|----|----|----|----|
|mode|`Social login position`|n/a|`popup`, `checkout_cart`|
