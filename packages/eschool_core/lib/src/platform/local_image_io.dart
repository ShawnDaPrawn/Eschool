import 'dart:io';

import 'package:flutter/widgets.dart';
import 'package:flutter_svg/svg.dart';

Widget localImage(String path, {required BoxFit fit}) =>
    path.toLowerCase().endsWith('svg')
    ? SvgPicture.file(File(path), fit: fit)
    : Image.file(File(path), fit: fit);
