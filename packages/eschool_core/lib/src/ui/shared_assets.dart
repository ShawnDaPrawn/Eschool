/// Paths of the images and animations the shared widgets draw. The files are
/// in each app's own `assets/` folder (both apps ship the same files under the
/// same names), so the paths load from whichever app is running. Each app's
/// `Assets` class points its entries for these files here.
// ignore: avoid_classes_with_only_static_members
abstract final class SharedAssets {
  static const _imagePath = 'assets/images';
  static const _brandingImagePath = 'assets/images/branding';
  static const _animationsPath = 'assets/animations';

  static const fileNotFoundImage = '$_brandingImagePath/fileNotFound.svg';
  static const noInternetImage = '$_brandingImagePath/noInternet.svg';
  static const somethingWentWrongImage =
      '$_brandingImagePath/somethingWentWrong.svg';

  static const rtlBackIcon = '$_imagePath/rtl_back_icon.svg';
  static const backIcon = '$_imagePath/back_icon.svg';
  static const hidePasswordIcon = '$_imagePath/hide_password.svg';
  static const showPasswordIcon = '$_imagePath/show_password.svg';
  static const downloadIcon = '$_imagePath/download_icon.svg';
  static const defaultProfile = '$_imagePath/default_profile.svg';
  static const arrowDownIcon = '$_imagePath/arrow_down_icon.svg';

  static const addFloatingAnimation = '$_animationsPath/add_floating.json';
}
