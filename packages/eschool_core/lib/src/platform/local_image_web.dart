import 'package:flutter/widgets.dart';
import 'package:flutter_svg/svg.dart';

Widget localImage(String path, {required BoxFit fit}) =>
    path.toLowerCase().endsWith('svg')
    ? SvgPicture.network(path, fit: fit)
    : Image.network(path, fit: fit);
