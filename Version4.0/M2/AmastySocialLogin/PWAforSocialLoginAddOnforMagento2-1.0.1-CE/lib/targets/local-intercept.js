module.exports = targets => {
  targets.of('@magento/pwa-buildpack').specialFeatures.tap(flags => {
    flags[targets.name] = {
      cssModules: true,
      esModules: true,
      graphqlQueries: true
    };
  });

  const venia = targets.of('@magento/venia-ui');
  const peregrineTargets = targets.of('@magento/peregrine');

  const routes = venia.routes;

  routes.tap(routesArray => {
    routesArray.push({
      name: 'MySocialAccounts',
      pattern: '/social-accounts',
      exact: true,
      path: targets.name + '/src/components/SocialAccountsPage'
    });

    return routesArray;
  });

  const talonsTarget = peregrineTargets.talons;

  talonsTarget.tap(({ AccountMenu, Header }) => {
    Header.useAccountMenu.wrapWith(
      targets.name + '/targets/wrapUseAccountMenu'
    );
    AccountMenu.useAccountMenuItems.wrapWith(
      targets.name + '/targets/wrapUseAccountMenuItems'
    );
  });
};
